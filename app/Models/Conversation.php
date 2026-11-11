<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['email', 'email_verified_at', 'start_key'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime', 'email_verified_at' => 'datetime', 'last_notified_at' => 'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
