<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['qr_token'];

    protected function casts(): array
    {
        return ['acquired_on' => 'date', 'acquisition_value' => 'decimal:2', 'verified' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Asset $a) => $a->qr_token ??= Str::random(48));
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function ownership()
    {
        return $this->belongsTo(Ownership::class);
    }

    public function procurementItem()
    {
        return $this->belongsTo(ProcurementItem::class);
    }

    public function events()
    {
        return $this->hasMany(AssetEvent::class)->orderByDesc('id');
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class)->orderByDesc('id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class)->orderByDesc('performed_on');
    }
}
