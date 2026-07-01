<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    public $table = 'chats';

    protected $guarded = [];

    public function member()
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAuthorNameAttribute(): ?string
    {
        if ($this->member) {
            return $this->member->nama_lengkap;
        }

        if ($this->user) {
            return $this->user->name ?: $this->user->email;
        }

        return null;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function projectMp()
    {
        return $this->belongsTo(ProjectMp::class);
    }
}
