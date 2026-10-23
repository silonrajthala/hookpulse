<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            $tenantId = app(TenantContext::class)->getTenantId();
            if (! $model->tenant_id && $tenantId) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}
