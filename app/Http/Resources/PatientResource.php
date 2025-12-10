<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cpf' => $this->cpf,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->age,
            'avatar' => $this->avatar,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'status' => $this->status,
            'financial_status' => $this->financial_status,
            'total_to_pay' => $this->total_to_pay,
            'total_paid' => $this->total_paid,
            'notes' => $this->notes,
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'sessions' => SessionResource::collection($this->whenLoaded('sessions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
