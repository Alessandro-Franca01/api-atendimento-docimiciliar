<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Session;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuário de teste
        $user = User::create([
            'name' => 'Dr. Carlos Silva',
            'email' => 'dr.carlos@fisiogestor.com',
            'password' => Hash::make('password'),
            'phone' => '(11) 98765-4321',
            'cpf' => '123.456.789-00',
            'specialty' => 'Fisioterapeuta',
        ]);

        // Criar pacientes de exemplo
        $patient1 = Patient::create([
            'user_id' => $user->id,
            'name' => 'Ana Silva',
            'email' => 'ana.silva@email.com',
            'phone' => '(11) 98765-4321',
            'cpf' => '987.654.321-00',
            'birth_date' => '1990-05-15',
            'age' => 34,
            'emergency_contact_name' => 'Maria Souza',
            'emergency_contact_phone' => '(11) 98765-0000',
            'status' => 'Ativo',
        ]);

        // Adicionar endereços
        $patient1->addresses()->create([
            'type' => 'Residencial',
            'street' => 'Rua das Flores',
            'number' => '123',
            'complement' => 'Apto 45',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567',
            'is_primary' => true,
        ]);

        $patient2 = Patient::create([
            'user_id' => $user->id,
            'name' => 'Carlos Souza',
            'email' => 'carlos.souza@email.com',
            'phone' => '(11) 99999-8888',
            'cpf' => '111.222.333-44',
            'birth_date' => '1985-08-20',
            'age' => 39,
            'status' => 'Ativo',
        ]);

        // Criar sessão
        $session = Session::create([
            'patient_id' => $patient1->id,
            'user_id' => $user->id,
            'title' => 'Tratamento de Fisioterapia',
            'total_appointments' => 10,
            'completed_appointments' => 3,
            'total_value' => 1500.00,
            'paid_value' => 450.00,
            'start_date' => now()->subDays(30),
            'status' => 'Ativa',
        ]);

        // Criar horários fixos da sessão
        $session->schedules()->createMany([
            [
                'day_of_week' => 'Segunda-feira',
                'time' => '10:00',
            ],
            [
                'day_of_week' => 'Quarta-feira',
                'time' => '10:00',
            ],
        ]);

        // Criar atendimentos
        Appointment::create([
            'patient_id' => $patient1->id,
            'user_id' => $user->id,
            'session_id' => $session->id,
            'date' => now()->addDays(1),
            'scheduled_time' => '10:00',
            'type' => 'Fisioterapia',
            'status' => 'Pendente',
        ]);

        Appointment::create([
            'patient_id' => $patient2->id,
            'user_id' => $user->id,
            'date' => now()->addDays(2),
            'scheduled_time' => '14:00',
            'type' => 'Avaliação',
            'status' => 'Confirmado',
        ]);

        // Criar pagamentos
        Payment::create([
            'patient_id' => $patient1->id,
            'user_id' => $user->id,
            'session_id' => $session->id,
            'amount' => 150.00,
            'payment_date' => now()->subDays(10),
            'payment_method' => 'Pix',
            'status' => 'Pago',
        ]);

        Payment::create([
            'patient_id' => $patient2->id,
            'user_id' => $user->id,
            'amount' => 200.00,
            'payment_date' => now(),
            'payment_method' => 'Dinheiro',
            'status' => 'Pendente',
        ]);
    }
}

