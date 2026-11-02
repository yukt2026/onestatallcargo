<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'phone',
        'company_name',
        'brand_name',
        'gstin',
        'pan_number',
        'business_type',
        'company_address',
        'company_city',
        'company_state',
        'company_pincode',
        'website',
        'support_phone',
        'bank_name',
        'account_number',
        'ifsc_code',
        'account_holder_name',
        'wallet_balance',
        'permissions',
        'latitude',
        'longitude',
        'last_location_at',
        'status',
        'early_cod_plan',
        'early_cod_fee',
        'label_settings',
    ];

    protected $hidden = [
        'password',
        'avatar',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'label_settings' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'operations']);
    }

    public function isSeller(): bool
    {
        return in_array($this->role, ['seller', 'aggregator', 'b2b_customer', 'corporate']);
    }

    public function isFranchise(): bool
    {
        return $this->role === 'franchise';
    }

    public function isRider(): bool
    {
        return in_array($this->role, ['rider', 'pickup_rider', 'delivery_rider']);
    }

    public function kyc()
    {
        return $this->hasOne(Kyc::class);
    }

    public function isKycApproved(): bool
    {
        return $this->kyc && $this->kyc->status === 'approved';
    }

    public function rider()
    {
        return $this->hasOne(Rider::class, 'user_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'user_id');
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}