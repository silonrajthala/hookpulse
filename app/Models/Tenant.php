<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'api_key',
    ];

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
