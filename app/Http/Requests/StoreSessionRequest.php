<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'title' => 'nullable|string|max:255',
            'total_appointments' => 'required|integer|min:1|max:100',
            'total_value' => 'required|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'observations' => 'nullable|string',
            'appointment_type' => 'nullable|in:Fisioterapia,Pilates,Avaliação,Reabilitação,Outro',

            'schedules' => 'required|array|min:1|max:7',
            'schedules.*.day_of_week' => 'required|in:Segunda-feira,Terça-feira,Quarta-feira,Quinta-feira,Sexta-feira,Sábado,Domingo',
            'schedules.*.time' => 'required|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Selecione um paciente.',
            'patient_id.exists' => 'Paciente não encontrado.',
            'total_appointments.required' => 'Informe o número total de atendimentos.',
            'total_appointments.min' => 'Deve haver pelo menos 1 atendimento.',
            'total_appointments.max' => 'O máximo é 100 atendimentos por sessão.',
            'total_value.required' => 'Informe o valor total da sessão.',
            'start_date.required' => 'Informe a data de início.',
            'start_date.after_or_equal' => 'A data de início não pode ser no passado.',
            'schedules.required' => 'Defina pelo menos um horário fixo.',
            'schedules.min' => 'Defina pelo menos um horário fixo.',
            'schedules.max' => 'Máximo de 7 horários (um por dia da semana).',
        ];
    }
}
