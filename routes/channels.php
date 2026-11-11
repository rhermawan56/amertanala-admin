<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.inbox', fn ($user) => (bool) $user->is_admin);
Broadcast::channel('chat.{id}', fn ($user) => (bool) $user->is_admin);
