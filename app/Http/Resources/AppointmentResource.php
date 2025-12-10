<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'session_id' => $this->session_id,
            'session' => new SessionResource($this->whenLoaded('session')),
            'date' => $this->date->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'type' => $this->type,
            'status' => $this->status,
            'observations' => $this->observations,
            'session_notes' => $this->session_notes,
            'attachments' => $this->attachments,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
