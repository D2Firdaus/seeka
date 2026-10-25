<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'nim_nip',
    'phone',
    'notify_email',
    'notify_whatsapp',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $primaryKey = 'user_id';

    protected $attributes = [
        'role' => 'user',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_whatsapp' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'user_id', 'user_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'claimant_id', 'user_id');
    }

    public function verifiedClaims(): HasMany
    {
        return $this->hasMany(Claim::class, 'verified_by', 'user_id');
    }

    public function handoversGiven(): HasMany
    {
        return $this->hasMany(Handover::class, 'handed_by', 'user_id');
    }

    public function handoversReceived(): HasMany
    {
        return $this->hasMany(Handover::class, 'received_by', 'user_id');
    }

    public function disputesReported(): HasMany
    {
        return $this->hasMany(Dispute::class, 'reported_by', 'user_id');
    }

    public function disputesResolved(): HasMany
    {
        return $this->hasMany(Dispute::class, 'resolved_by', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function itemLogs(): HasMany
    {
        return $this->hasMany(ItemLog::class, 'user_id', 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id', 'user_id');
    }
}
