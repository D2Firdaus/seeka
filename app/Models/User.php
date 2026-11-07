<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

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
class User extends Authenticatable implements MustVerifyEmail
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
            'verification_sent_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_whatsapp' => 'boolean',
        ];
    }

    /**
     * Kembalikan NIM/NIP dari bagian lokal email bila berupa angka yang belum dipakai.
     */
    public static function nimNipFromEmail(string $email, ?int $exceptUserId = null): ?string
    {
        $localPart = Str::before($email, '@');

        if (preg_match('/^[0-9]{6,30}$/', $localPart) !== 1) {
            return null;
        }

        $isTaken = static::query()
            ->where('nim_nip', $localPart)
            ->when($exceptUserId !== null, fn ($query) => $query->where('user_id', '!=', $exceptUserId))
            ->exists();

        return $isTaken ? null : $localPart;
    }

    /**
     * Kirim notifikasi aktivasi email sambil mencatat waktu pengirimannya.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->forceFill(['verification_sent_at' => now()])->save();

        parent::sendEmailVerificationNotification();
    }

    /**
     * Sisa detik cooldown sebelum email aktivasi boleh dikirim ulang (0 bila sudah boleh).
     */
    public function verificationCooldownRemaining(): int
    {
        if ($this->verification_sent_at === null) {
            return 0;
        }

        return max(0, (int) (60 - now()->diffInSeconds($this->verification_sent_at, true)));
    }

    /**
     * Hash link aktivasi yang berlaku untuk verification_sent_at saat ini.
     *
     * Mengirim email baru mengubah verification_sent_at sehingga hash link lama otomatis tidak berlaku.
     */
    public function verificationHash(): string
    {
        return sha1($this->getEmailForVerification().($this->verification_sent_at?->getTimestamp() ?? ''));
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
