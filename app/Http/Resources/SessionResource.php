<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'title' => $this->title,
            'total_appointments' => $this->total_appointments,
            'completed_appointments' => $this->completed_appointments,
            'total_value' => (float) $this->total_value,
            'paid_value' => (float) $this->paid_value,
            'remaining_balance' => $this->remaining_balance,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'observations' => $this->observations,
            'schedules' => SessionScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

