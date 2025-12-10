<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'is_primary' => $this->is_primary,
            'full_address' => "{$this->street}, {$this->number}, {$this->neighborhood}, {$this->city} - {$this->state}, {$this->zip_code}",
        ];
    }
}
