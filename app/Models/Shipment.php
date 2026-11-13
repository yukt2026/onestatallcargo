<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedRider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function mediaEvidences()
    {
        return $this->hasMany(MediaEvidence::class);
    }

    public function events()
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function franchise()
    {
        return $this->belongsTo(Franchise::class, 'franchise_id');
    }

    public function districtFranchise()
    {
        return $this->belongsTo(Franchise::class, 'franchise_id');
    }

    public function stateFranchise()
    {
        return $this->belongsTo(Franchise::class, 'state_franchise_id');
    }

    public function pickupFranchise()
    {
        return $this->belongsTo(Franchise::class, 'pickup_franchise_id');
    }

    public function commissions()
    {
        return $this->hasMany(FranchiseCommission::class);
    }

    public function getDisplayAwbAttribute(): string
    {
        return !empty($this->courier_awb) ? (string)$this->courier_awb : (string)$this->awb_number;
    }

    public function isThirdPartyCourier(): bool
    {
        return !empty($this->courier_partner) && stripos($this->courier_partner, 'onestall') === false;
    }
}
