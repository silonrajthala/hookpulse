<?php

namespace App\Services;

class TenantContext
{
    protected ?int $tenantId = null;

    public function setTenantId(?int $id): void
    {
        $this->tenantId = $id;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }
}
