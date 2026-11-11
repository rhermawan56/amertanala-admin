<?php

namespace Tests\Feature;

use App\Http\Controllers\ContentController;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_dashboard_is_private_and_requires_an_admin_without_mfa(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertForbidden();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->get('/security')->assertOk();
        $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->component('workspace/Dashboard')->where('stats.conversations', 0));
    }

    public function test_public_registration_and_non_admin_login_are_refused(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'attacker@example.test', 'is_admin' => true])->assertNotFound();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_previously_enrolled_admin_logs_in_with_password_without_changing_mfa_data(): void
    {
        $this->get('/login')->assertOk()->assertCookie('AMERTANALA-XSRF')->assertCookieMissing('XSRF-TOKEN');
        $user = $this->admin();
        $user->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_recovery_codes' => encrypt(json_encode(['recovery-fixture'])), 'two_factor_confirmed_at' => now()])->save();
        $originalSecret = $user->two_factor_secret;
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
        $this->assertSame($originalSecret, $user->fresh()->two_factor_secret);
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertContains('recovery-fixture', $user->fresh()->recoveryCodes());
    }

    public function test_idle_and_absolute_session_limits_are_enforced(): void
    {
        $this->freezeTime();
        $this->actingAs($this->admin())->withSession(['admin_started_at' => now()->subHours(8)->timestamp, 'admin_last_seen_at' => now()->timestamp])->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
        $this->actingAs($this->admin())->withSession(['admin_started_at' => now()->timestamp, 'admin_last_seen_at' => now()->subMinutes(16)->timestamp])->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_drafts_are_private_until_publication_and_stale_writes_are_refused(): void
    {
        $this->actingAs($this->admin());
        $site = ContentController::site();
        $content = $site->draft;
        $content['hero_title'] = 'Cerita yang baru';
        $this->getJson('/api/public/landing')->assertNotFound();
        $this->put('/content', ['version' => 1, 'content' => $content])->assertRedirect();
        $this->assertSame('Cerita yang baru', $site->fresh()->draft['hero_title']);
        $this->getJson('/api/public/landing')->assertNotFound();
        $this->put('/content', ['version' => 1, 'content' => $content])->assertConflict();
        $this->post('/content/publish', ['version' => 1])->assertConflict();
        $this->post('/content/publish', ['version' => 2])->assertRedirect();
        $this->getJson('/api/public/landing')->assertOk()->assertJsonPath('content.hero_title', 'Cerita yang baru')->assertJsonMissingPath('draft');
        $content['hero_title'] = 'Draf privat';
        $this->put('/content', ['version' => 3, 'content' => $content])->assertRedirect();
        $this->getJson('/api/public/landing')->assertJsonPath('content.hero_title', 'Cerita yang baru');
        $this->assertDatabaseCount('content_revisions', 3);
    }

    public function test_invalid_content_cannot_be_saved_and_non_admin_cannot_publish(): void
    {
        $content = ContentController::site()->draft;
        $content['themes'][0]['preview_url'] = 'javascript:alert(1)';
        $this->actingAs($this->admin())->putJson('/content', ['version' => 1, 'content' => $content])->assertUnprocessable()->assertJsonValidationErrors('content.themes.0.preview_url');
        $this->assertSame(1, SiteContent::first()->version);
        $this->actingAs(User::factory()->create())->post('/content/publish', ['version' => 1])->assertForbidden();
    }

    public function test_restoring_a_revision_changes_only_the_draft(): void
    {
        $this->actingAs($this->admin());
        $site = ContentController::site();
        $this->put('/content', ['version' => 1, 'content' => $site->draft])->assertRedirect();
        $this->post('/content/publish', ['version' => 2])->assertRedirect();
        $content = $site->fresh()->draft;
        $content['brand'] = 'Changed';
        $this->put('/content', ['version' => 3, 'content' => $content])->assertRedirect();
        $this->post('/content/restore/1', ['version' => 4])->assertRedirect();
        $this->assertSame('amertanala.', $site->fresh()->draft['brand']);
        $this->assertSame('amertanala.', $site->fresh()->published['brand']);
    }

    public function test_media_upload_is_restricted_to_images_and_admins(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post('/media', ['image' => UploadedFile::fake()->create('payload.svg', 1, 'image/svg+xml'), 'alt' => 'SVG'])->assertSessionHasErrors('image');
        $this->post('/media', ['image' => UploadedFile::fake()->image('preview.png', 20, 20), 'alt' => 'Koleksi undangan'])->assertRedirect();
        $path = DB::table('media_assets')->value('path');
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('media_assets', ['alt' => 'Koleksi undangan', 'mime' => 'image/png']);
        $this->actingAs(User::factory()->create())->get('/media')->assertForbidden();
    }

    public function test_new_admin_can_login_and_open_workspace_without_mfa(): void
    {
        $user = $this->admin();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        foreach (['/dashboard', '/content', '/media', '/inbox', '/security'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_mfa_routes_are_unavailable_while_mfa_is_deferred(): void
    {
        $this->actingAs($this->admin());
        foreach (['/two-factor-challenge', '/user/two-factor-qr-code', '/user/two-factor-recovery-codes'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->postJson('/user/two-factor-authentication')->assertNotFound();
        $this->postJson('/user/confirmed-two-factor-authentication', ['code' => '123456'])->assertNotFound();
    }

    public function test_private_admin_channels_work_without_mfa_but_reject_non_admins(): void
    {
        $admin = $this->admin();
        foreach (['private-admin.inbox', 'private-chat.example'] as $channel) {
            $this->actingAs($admin)->postJson('/broadcasting/auth', ['channel_name' => $channel, 'socket_id' => '123.456'])->assertOk()->assertJsonStructure(['auth']);
            $this->actingAs(User::factory()->create())->postJson('/broadcasting/auth', ['channel_name' => $channel, 'socket_id' => '123.456'])->assertForbidden();
        }
    }

    public function test_admin_can_change_password_without_enrolling_mfa(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->putJson('/user/password', ['current_password' => 'wrong', 'password' => 'New!Password2947', 'password_confirmation' => 'New!Password2947'])->assertUnprocessable();
        $this->putJson('/user/password', ['current_password' => 'password', 'password' => 'New!Password2947', 'password_confirmation' => 'New!Password2947'])->assertOk();
        $this->assertTrue(Hash::check('New!Password2947', $user->fresh()->password));
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }
}
