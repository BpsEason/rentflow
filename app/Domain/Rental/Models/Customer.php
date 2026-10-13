<?php

namespace App\Domain\Rental\Models;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'email',
        'phone',
        'is_active',
        'id_number',
        'license_number',
        'license_expiry',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'license_expiry' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
