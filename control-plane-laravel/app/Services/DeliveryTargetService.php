<?php

namespace App\Services;

use App\Models\DeliveryTarget;
use Illuminate\Database\Eloquent\Collection;

class DeliveryTargetService
{
    public function getAll(): Collection
    {
        return DeliveryTarget::all();
    }

    public function create(array $data): DeliveryTarget
    {
        return DeliveryTarget::create($data);
    }

    public function update(DeliveryTarget $deliveryTarget, array $data): DeliveryTarget
    {
        $deliveryTarget->update($data);
        return $deliveryTarget;
    }

    public function delete(DeliveryTarget $deliveryTarget): void
    {
        $deliveryTarget->delete();
    }
}
