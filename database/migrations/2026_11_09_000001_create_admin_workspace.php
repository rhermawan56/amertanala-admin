<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('draft');
            $table->json('published')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('content');
            $table->string('action');
            $table->timestamps();
        });
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('theme', 150)->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamp('last_activity_at')->index();
            $table->unsignedBigInteger('admin_read_id')->default(0);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender');
            $table->uuid('client_id');
            $table->text('body');
            $table->timestamps();
            $table->unique(['conversation_id', 'sender', 'client_id']);
        });
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_seen_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::create('chat_access_links', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('purpose');
            $table->string('email');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('name');
            $table->string('alt')->default('');
            $table->string('mime');
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['media_assets', 'chat_access_links', 'chat_sessions', 'messages', 'conversations', 'content_revisions', 'site_contents'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
