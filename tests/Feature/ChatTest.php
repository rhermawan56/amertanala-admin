<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ChatAccessLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'null']);
        $this->withCredentials();
    }

    private function guest(): array
    {
        $conversation = Conversation::create(['name' => 'Pengunjung', 'theme' => 'Botanical Sage', 'last_activity_at' => now()]);
        $token = bin2hex(random_bytes(32));
        $session = ChatSession::create(['conversation_id' => $conversation->id, 'token_hash' => hash('sha256', $token), 'last_seen_at' => now(), 'expires_at' => now()->addDays(7)]);

        return [$conversation, $token, $session];
    }

    public function test_guest_can_start_chat_and_theme_is_resolved_by_server(): void
    {
        $this->postJson('/chat/start', ['name' => 'Andi', 'theme_id' => 'botanical-sage', 'body' => 'Halo', 'client_id' => Str::uuid()->toString(), 'sender' => 'admin'])
            ->assertCreated()->assertJsonPath('conversation.theme', 'Botanical Sage')->assertJsonPath('message.sender', 'guest')->assertCookie(config('workspace.chat_cookie'))->assertJsonMissingPath('conversation.start_key');
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseHas('messages', ['body' => 'Halo', 'sender' => 'guest']);
        $this->assertDatabaseCount('chat_sessions', 1);
    }

    public function test_guest_cannot_read_without_valid_cookie_or_select_another_conversation(): void
    {
        [$first, $token] = $this->guest();
        [$second] = $this->guest();
        Message::create(['conversation_id' => $second->id, 'sender' => 'admin', 'body' => 'Rahasia pengunjung lain', 'client_id' => Str::uuid()]);
        $this->getJson('/chat/messages')->assertUnauthorized();
        $this->withCookie(config('workspace.chat_cookie'), $token)->getJson('/chat/messages?conversation_id='.$second->id)->assertOk()->assertJsonCount(0, 'messages')->assertJsonPath('conversation.id', $first->id);
        $this->postJson('/chat/broadcasting/auth', ['channel_name' => 'private-chat.'.$second->id, 'socket_id' => '123.456'])->assertForbidden();
    }

    public function test_guest_cannot_impersonate_admin_and_retries_do_not_duplicate_messages(): void
    {
        [$conversation, $token] = $this->guest();
        $data = ['body' => '<script>alert(1)</script>', 'sender' => 'admin', 'client_id' => Str::uuid()->toString()];
        $this->withCookie(config('workspace.chat_cookie'), $token)->postJson('/chat/messages', $data)->assertOk()->assertJsonPath('message.sender', 'guest');
        $data['body'] = 'Changed retry';
        $this->postJson('/chat/messages', $data)->assertOk()->assertJsonPath('message.body', '<script>alert(1)</script>');
        $this->assertDatabaseCount('messages', 1);
        $this->assertSame(1, $conversation->messages()->count());
    }

    public function test_expired_or_idle_chat_access_is_refused(): void
    {
        $this->freezeTime();
        [, $token, $session] = $this->guest();
        $session->update(['last_seen_at' => now()->subHours(25)]);
        $this->withCookie(config('workspace.chat_cookie'), $token)->getJson('/chat/messages')->assertUnauthorized();
        $session->update(['last_seen_at' => now(), 'expires_at' => now()->subSecond()]);
        $this->getJson('/chat/messages')->assertUnauthorized();
    }

    public function test_message_validation_and_spam_limit_are_enforced(): void
    {
        [$conversation, $token] = $this->guest();
        $this->withCookie(config('workspace.chat_cookie'), $token)->postJson('/chat/messages', ['body' => str_repeat('x', 2001), 'client_id' => Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('body');
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/chat/messages', ['body' => 'Pesan '.$i, 'client_id' => Str::uuid()])->assertOk();
        }
        $this->postJson('/chat/messages', ['body' => 'Pesan tambahan', 'client_id' => Str::uuid()])->assertTooManyRequests();
        $this->assertSame(10, $conversation->messages()->count());
    }

    public function test_admin_replies_only_to_the_bound_conversation(): void
    {
        [$first] = $this->guest();
        [$second] = $this->guest();
        $admin = User::factory()->create(['is_admin' => true]);
        $data = ['body' => 'Balasan untuk Andi', 'client_id' => Str::uuid()->toString(), 'conversation_id' => $second->id];
        $this->actingAs($admin)->postJson('/inbox/'.$first->id.'/messages', $data)->assertOk()->assertJsonPath('message.conversation_id', $first->id);
        $this->postJson('/inbox/'.$first->id.'/messages', $data)->assertOk();
        $this->assertSame(1, $first->messages()->count());
        $this->assertSame(0, $second->messages()->count());
    }

    public function test_email_recovery_requires_verification_and_has_generic_response(): void
    {
        Notification::fake();
        config(['mail.default' => 'smtp']);
        [$conversation, $token] = $this->guest();
        $this->withCookie(config('workspace.chat_cookie'), $token)->postJson('/chat/access', ['email' => 'andi@example.test', 'purpose' => 'verify'])->assertOk();
        $this->assertNull($conversation->fresh()->email_verified_at);
        Notification::assertSentOnDemand(ChatAccessLink::class);
        $this->postJson('/chat/access', ['email' => 'unknown@example.test', 'purpose' => 'recover'])->assertOk()->assertJsonPath('message', 'Jika permintaan memenuhi syarat, tautan akan dikirim. Periksa email Anda.');
        $this->assertDatabaseCount('chat_access_links', 1);
    }

    public function test_recovery_get_does_not_consume_token_and_post_rotates_access_once(): void
    {
        [$conversation, $oldToken] = $this->guest();
        $token = str_repeat('a', 64);
        DB::table('chat_access_links')->insert(['conversation_id' => $conversation->id, 'token_hash' => hash('sha256', $token), 'purpose' => 'recover', 'email' => 'andi@example.test', 'expires_at' => now()->addMinutes(15), 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/chat/access/'.$token)->assertOk();
        $this->assertDatabaseCount('chat_access_links', 1);
        $this->post('/chat/access/'.$token)->assertRedirect('/chat')->assertCookie(config('workspace.chat_cookie'));
        $this->assertDatabaseCount('chat_access_links', 0);
        $this->assertDatabaseMissing('chat_sessions', ['token_hash' => hash('sha256', $oldToken)]);
        $this->post('/chat/access/'.$token)->assertGone();
    }

    public function test_verify_link_cannot_be_confirmed_from_another_browser(): void
    {
        [$conversation] = $this->guest();
        $token = str_repeat('b', 64);
        DB::table('chat_access_links')->insert(['conversation_id' => $conversation->id, 'token_hash' => hash('sha256', $token), 'purpose' => 'verify', 'email' => 'andi@example.test', 'expires_at' => now()->addMinutes(15), 'created_at' => now(), 'updated_at' => now()]);
        $this->post('/chat/access/'.$token)->assertForbidden();
        $this->assertNull($conversation->fresh()->email_verified_at);
    }

    public function test_maintenance_deletes_inactive_chat_and_dependent_access(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        [$old] = $this->guest();
        [$active] = $this->guest();
        $old->update(['last_activity_at' => now()->subDays(91)]);
        $this->artisan('chat:maintenance')->assertSuccessful();
        $this->assertDatabaseMissing('conversations', ['id' => $old->id]);
        $this->assertDatabaseMissing('chat_sessions', ['conversation_id' => $old->id]);
        $this->assertDatabaseHas('conversations', ['id' => $active->id]);
    }

    public function test_retrying_initial_request_without_response_cookie_reuses_the_conversation(): void
    {
        $page = $this->get('/chat')->assertOk();
        $this->withCookie(config('session.cookie'), $page->getCookie(config('session.cookie'))->getValue());
        $data = ['name' => 'Andi', 'body' => 'Pesan pertama', 'client_id' => Str::uuid()->toString()];
        $first = $this->postJson('/chat/start', $data)->assertCreated();
        $this->postJson('/chat/start', $data)->assertOk()->assertJsonPath('conversation.id', $first->json('conversation.id'));
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_guest_realtime_channel_requires_its_own_active_session(): void
    {
        [, $token, $session] = $this->guest();
        $this->withCookie(config('workspace.chat_cookie'), $token)->postJson('/chat/broadcasting/auth', [
            'channel_name' => 'private-guest-session.'.$session->id,
            'socket_id' => '123.456',
        ])->assertOk()->assertJsonStructure(['auth']);
        $session->update(['expires_at' => now()->subSecond()]);
        $this->postJson('/chat/broadcasting/auth', [
            'channel_name' => 'private-guest-session.'.$session->id,
            'socket_id' => '123.456',
        ])->assertUnauthorized();
    }

    public function test_email_recovery_clearly_reports_unconfigured_delivery(): void
    {
        config(['mail.default' => 'array']);
        $this->postJson('/chat/access', ['email' => 'andi@example.test', 'purpose' => 'recover'])
            ->assertServiceUnavailable()->assertJsonPath('message', 'Pemulihan email belum tersedia. Pertahankan cookie chat Anda.');
    }
}
