<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'appointment_id' => $this->appointment_id,
            'session_id' => $this->session_id,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
