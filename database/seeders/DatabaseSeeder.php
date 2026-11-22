<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Assessment;
use App\Models\Clinic;
use App\Models\HealthPlan;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados de demonstração para testes manuais.
 *
 * É reexecutável: a cada execução apaga e recria os dados do usuário de
 * demonstração (dr.carlos@fisiogestor.com), sem mexer em dados de outros usuários.
 *
 * Datas são relativas a "hoje", então a agenda sempre tem atendimentos
 * passados, de hoje e futuros.
 */
class DatabaseSeeder extends Seeder
{
    private const DAY_INDEX = [
        'Domingo' => 0, 'Segunda-feira' => 1, 'Terça-feira' => 2, 'Quarta-feira' => 3,
        'Quinta-feira' => 4, 'Sexta-feira' => 5, 'Sábado' => 6,
    ];

    private User $user;

    public function run(): void
    {
        $clinic = Clinic::firstOrCreate(
            ['cnpj' => '12.345.678/0001-90'],
            ['name' => 'Clínica Movimento', 'email' => 'contato@clinicamovimento.com', 'phone' => '(11) 3333-4444']
        );

        $this->user = User::updateOrCreate(
            ['email' => 'dr.carlos@fisiogestor.com'],
            [
                'clinic_id' => $clinic->id,
                'name' => 'Dr. Carlos Silva',
                'password' => Hash::make('password'),
                'phone' => '(11) 98765-4321',
                'cpf' => '123.456.789-00',
                'specialty' => 'Fisioterapeuta',
            ]
        );

        // Limpa os dados de demonstração anteriores (FKs em cascade removem o resto)
        Patient::withTrashed()->where('user_id', $this->user->id)->forceDelete();

        $plans = [
            'unimed' => HealthPlan::updateOrCreate(['name' => 'Unimed'], ['value' => 60.00, 'status' => 'active']),
            'bradesco' => HealthPlan::updateOrCreate(['name' => 'Bradesco Saúde'], ['value' => 55.00, 'status' => 'active']),
            'sulamerica' => HealthPlan::updateOrCreate(['name' => 'SulAmérica'], ['value' => 70.00, 'status' => 'inactive']),
        ];

        $p = $this->createPatients();

        // ---------------------------------------------------------------
        // 1. Ana: particular, em andamento, pagamentos em dia (parcelado)
        // ---------------------------------------------------------------
        $s = $this->createSession($p['ana'], 'Tratamento de Fisioterapia', 10, 1500.00, 'private', null, 21, [
            ['Segunda-feira', '10:00'], ['Quarta-feira', '10:00'],
        ]);
        $this->payments($p['ana'], $s, [
            [150, 'Pix', 'Pago', -20], [150, 'Pix', 'Pago', -10], [150, 'Dinheiro', 'Pago', -3],
            [300, 'Pix', 'Pendente', 7],
        ]);
        $this->assessment($p['ana'], $s, 6, 'Dor lombar crônica',
            'Lombalgia mecânica com irradiação para glúteo direito.', 'Fortalecimento de core e mobilidade lombar.');

        // ---------------------------------------------------------------
        // 2. Carlos: particular, concluída e totalmente paga
        // ---------------------------------------------------------------
        $s = $this->createSession($p['carlos'], 'Reabilitação de Joelho', 8, 1040.00, 'private', null, 60, [
            ['Terça-feira', '08:00'], ['Quinta-feira', '08:00'],
        ], allowMiss: false);
        $this->payments($p['carlos'], $s, [[520, 'Cartao', 'Pago', -55], [520, 'Pix', 'Pago', -30]]);
        $this->assessment($p['carlos'], $s, 4, 'Dor no joelho esquerdo ao subir escadas',
            'Pós-operatório de LCA, 3 meses.', 'Ganho de ADM e fortalecimento de quadríceps.');

        // ---------------------------------------------------------------
        // 3. Beatriz: clínica / Unimed, pagamentos de convênio pendentes
        // ---------------------------------------------------------------
        $s = $this->createSession($p['beatriz'], 'Pilates Clínico', 12, 12 * 60.00, 'clinic', $plans['unimed'], 28, [
            ['Segunda-feira', '14:00'], ['Quarta-feira', '14:00'], ['Sexta-feira', '14:00'],
        ], 'room1');
        $this->payments($p['beatriz'], $s, [
            [240, 'Convenio', 'Pago', -14], [240, 'Convenio', 'Pendente', 10], [240, 'Convenio', 'Pendente', 20],
        ]);

        // ---------------------------------------------------------------
        // 4. João: particular com pagamentos ATRASADOS
        // ---------------------------------------------------------------
        $s = $this->createSession($p['joao'], 'Tratamento de Ombro', 10, 1200.00, 'private', null, 24, [
            ['Terça-feira', '16:00'], ['Quinta-feira', '16:00'],
        ]);
        $this->payments($p['joao'], $s, [
            [240, 'Pix', 'Pago', -24], [240, 'Pix', 'Atrasado', -10], [240, 'Dinheiro', 'Atrasado', -3],
            [480, 'Pix', 'Pendente', 12],
        ]);
        $this->assessment($p['joao'], $s, 7, 'Dor no ombro direito ao elevar o braço',
            'Tendinopatia do supraespinhal. Trabalha com carga de peso.', 'Redução da dor e retorno às atividades laborais.');

        // ---------------------------------------------------------------
        // 5. Marina: sessão CANCELADA (atendimentos pendentes cancelados)
        // ---------------------------------------------------------------
        $s = $this->createSession($p['marina'], 'Tratamento Cervical', 6, 720.00, 'private', null, 14, [
            ['Segunda-feira', '09:00'],
        ], 'no_room', 'Cancelada');
        $this->payments($p['marina'], $s, [[120, 'Pix', 'Pago', -13], [120, 'Pix', 'Cancelado', -5]]);

        // ---------------------------------------------------------------
        // 6. Pedro: clínica / Bradesco, em andamento
        // ---------------------------------------------------------------
        $s = $this->createSession($p['pedro'], 'Reabilitação Pós-AVC', 16, 16 * 55.00, 'clinic', $plans['bradesco'], 14, [
            ['Terça-feira', '11:00'], ['Quinta-feira', '11:00'],
        ], 'room2');
        $this->payments($p['pedro'], $s, [[220, 'Convenio', 'Pago', -7], [220, 'Convenio', 'Pendente', 14]]);

        // ---------------------------------------------------------------
        // 7. Lucia: ação social (gratuito)
        // ---------------------------------------------------------------
        $s = $this->createSession($p['lucia'], 'Fisioterapia Respiratória', 6, 0.00, 'private', null, 7, [
            ['Sexta-feira', '09:00'],
        ]);
        $this->payments($p['lucia'], $s, [[0, 'Gratuito', 'Acao_Social', -7]]);

        // ---------------------------------------------------------------
        // Atendimentos avulsos (sem sessão) — dão volume à agenda
        // ---------------------------------------------------------------
        $today = Carbon::today();
        $this->appointment($p['rafael'], null, $today, '09:00', 'Avaliação', 'Confirmado', 'Primeira avaliação.');
        $this->appointment($p['camila'], null, $today, '15:30', 'Pilates', 'Pendente');
        $this->appointment($p['rafael'], null, $today->copy()->addDays(2), '10:30', 'Reabilitação', 'Pendente');
        $this->appointment($p['camila'], null, $today->copy()->subDays(3), '17:00', 'Fisioterapia', 'Faltou', 'Paciente não compareceu.');
        $this->appointment($p['camila'], null, $today->copy()->subDays(9), '17:00', 'Fisioterapia', 'Realizado', null, 'Avaliação inicial e orientações domiciliares.');
        $this->appointment($p['rafael'], null, $today->copy()->subDays(5), '13:00', 'Outro', 'Cancelado', 'Cancelado pelo paciente.');

        // Pagamentos avulsos
        $this->payments($p['camila'], null, [[150, 'Dinheiro', 'Pago', -9], [150, 'Pix', 'Pendente', 0]]);
        $this->payments($p['rafael'], null, [[180, 'Pix', 'Pendente', 2]]);
    }

    /** @return array<string, Patient> */
    private function createPatients(): array
    {
        $rows = [
            'ana' => ['Ana Silva', 'ana.silva@email.com', '(11) 98765-4321', '987.654.321-00', '1990-05-15', 'Ativo', 'Feminino', 'Professora', 'Rua das Flores', '123', 'Apto 45', 'Centro', '01234-567', 'Maria Souza'],
            'carlos' => ['Carlos Souza', 'carlos.souza@email.com', '(11) 99999-8888', '111.222.333-44', '1985-08-20', 'Ativo', 'Masculino', 'Engenheiro', 'Av. Paulista', '1500', null, 'Bela Vista', '01310-100', 'Luiza Souza'],
            'beatriz' => ['Beatriz Lima', 'beatriz.lima@email.com', '(11) 97777-1212', '222.333.444-55', '1978-02-10', 'Ativo', 'Feminino', 'Advogada', 'Rua Augusta', '800', 'Sala 12', 'Consolação', '01304-001', 'Paulo Lima'],
            'joao' => ['João Pereira', null, '(11) 96666-3434', '333.444.555-66', '1969-11-03', 'Ativo', 'Masculino', 'Pedreiro', 'Rua do Comércio', '45', null, 'Mooca', '03105-000', 'Rosa Pereira'],
            'marina' => ['Marina Costa', 'marina.costa@email.com', '(11) 95555-5656', '444.555.666-77', '1995-07-27', 'Inativo', 'Feminino', 'Designer', 'Rua Oscar Freire', '300', 'Bloco B', 'Jardins', '01426-001', null],
            'pedro' => ['Pedro Almeida', 'pedro.almeida@email.com', '(11) 94444-7878', '555.666.777-88', '1950-01-30', 'Ativo', 'Masculino', 'Aposentado', 'Rua Vergueiro', '2200', 'Casa 2', 'Vila Mariana', '04101-300', 'Helena Almeida'],
            'lucia' => ['Lúcia Ferreira', null, '(11) 93333-9090', '666.777.888-99', '1958-09-12', 'Ativo', 'Feminino', 'Costureira', 'Rua da Paz', '77', null, 'Santo Amaro', '04720-010', 'Marcos Ferreira'],
            'rafael' => ['Rafael Gomes', 'rafael.gomes@email.com', '(11) 92222-1010', '777.888.999-00', '2001-04-18', 'Ativo', 'Masculino', 'Estudante', 'Rua Haddock Lobo', '595', 'Apto 81', 'Cerqueira César', '01414-001', 'Ivone Gomes'],
            'camila' => ['Camila Rocha', 'camila.rocha@email.com', '(11) 91111-2020', '888.999.000-11', '1988-12-25', 'Ativo', 'Feminino', 'Atleta amadora', 'Rua Pamplona', '1000', null, 'Jardim Paulista', '01405-001', 'Renato Rocha'],
        ];

        $patients = [];
        foreach ($rows as $key => [$name, $email, $phone, $cpf, $birth, $status, $gender, $occupation, $street, $number, $complement, $neighborhood, $cep, $emergency]) {
            $patient = Patient::create([
                'user_id' => $this->user->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'cpf' => $cpf,
                'birth_date' => $birth,
                'age' => Carbon::parse($birth)->age,
                'status' => $status,
                'gender' => $gender,
                'occupation' => $occupation,
                'emergency_contact_name' => $emergency,
                'emergency_contact_phone' => $emergency ? '(11) 98888-0000' : null,
                'notes' => $key === 'pedro' ? 'Hipertenso. Evitar exercícios de alta intensidade.' : null,
            ]);

            $patient->addresses()->create([
                'type' => 'Residencial',
                'street' => $street,
                'number' => $number,
                'complement' => $complement,
                'neighborhood' => $neighborhood,
                'city' => 'São Paulo',
                'state' => 'SP',
                'cep' => $cep,
                'is_primary' => true,
            ]);

            $patients[$key] = $patient;
        }

        return $patients;
    }

    /**
     * Cria a sessão e gera os atendimentos a partir dos horários fixos,
     * começando $daysAgo dias atrás. Atendimentos passados viram "Realizado"
     * (um "Faltou" por sessão), os de hoje "Confirmado" e os futuros "Pendente".
     */
    private function createSession(
        Patient $patient,
        string $title,
        int $total,
        float $value,
        string $category,
        ?HealthPlan $plan,
        int $daysAgo,
        array $schedules,
        string $room = 'no_room',
        string $status = 'Ativa',
        bool $allowMiss = true
    ): Session {
        $start = Carbon::today()->subDays($daysAgo);

        $session = Session::create([
            'patient_id' => $patient->id,
            'user_id' => $this->user->id,
            'category' => $category,
            'health_plan_id' => $plan?->id,
            'title' => $title,
            'total_appointments' => $total,
            'completed_appointments' => 0,
            'total_value' => $value,
            'start_date' => $start,
            'status' => 'Ativa',
            'observations' => 'Dados de demonstração.',
        ]);

        foreach ($schedules as [$day, $time]) {
            $session->schedules()->create(['day_of_week' => $day, 'time' => $time]);
        }

        $slots = collect($schedules)
            ->sortBy(fn ($s) => self::DAY_INDEX[$s[0]] . $s[1])
            ->values();

        $today = Carbon::today();
        $date = $start->copy();
        $created = 0;
        $completed = 0;
        $missedGiven = false;

        for ($i = 0; $i < 400 && $created < $total; $i++, $date->addDay()) {
            foreach ($slots as [$day, $time]) {
                if (self::DAY_INDEX[$day] !== $date->dayOfWeek || $created >= $total) {
                    continue;
                }

                if ($status === 'Cancelada') {
                    $apptStatus = $date->lt($today) ? 'Realizado' : 'Cancelado';
                } elseif ($date->lt($today)) {
                    $apptStatus = ($allowMiss && ! $missedGiven && $created === 2) ? 'Faltou' : 'Realizado';
                } elseif ($date->isSameDay($today)) {
                    $apptStatus = 'Confirmado';
                } else {
                    $apptStatus = 'Pendente';
                }
                $missedGiven = $missedGiven || $apptStatus === 'Faltou';

                $this->appointment(
                    $patient, $session, $date->copy(), $time, 'Fisioterapia', $apptStatus,
                    $apptStatus === 'Faltou' ? 'Paciente avisou tarde.' : null,
                    $apptStatus === 'Realizado' ? 'Evolução favorável, manter conduta.' : null,
                    $category, $plan?->id, $room
                );

                $completed += $apptStatus === 'Realizado' ? 1 : 0;
                $created++;
            }
        }

        $final = $status;
        if ($status === 'Ativa' && $completed >= $total) {
            $final = 'Concluída';
        }

        $session->update([
            'completed_appointments' => $completed,
            'status' => $final,
            'end_date' => $final === 'Ativa' ? null : $date->copy()->subDay(),
        ]);

        return $session;
    }

    private function appointment(
        Patient $patient,
        ?Session $session,
        Carbon $date,
        string $time,
        string $type,
        string $status,
        ?string $observations = null,
        ?string $notes = null,
        string $category = 'private',
        ?int $planId = null,
        string $room = 'no_room'
    ): Appointment {
        $done = $status === 'Realizado';

        return Appointment::create([
            'patient_id' => $patient->id,
            'user_id' => $this->user->id,
            'session_id' => $session?->id,
            'date' => $date,
            'scheduled_time' => $time,
            'start_time' => $done ? $time : null,
            'end_time' => $done ? Carbon::parse($time)->addMinutes(50)->format('H:i') : null,
            'type' => $type,
            'status' => $status,
            'observations' => $observations,
            'session_notes' => $notes,
            'category' => $category,
            'health_plan_id' => $planId,
            'room' => $room,
        ]);
    }

    /**
     * @param array<int, array{0: float|int, 1: string, 2: string, 3: int}> $rows
     *        [valor, forma, status, deslocamento em dias a partir de hoje]
     */
    private function payments(Patient $patient, ?Session $session, array $rows): void
    {
        foreach ($rows as [$amount, $method, $status, $offset]) {
            $when = Carbon::today()->addDays($offset);

            // Pagamentos "Pago" incrementam paid_value da sessão via evento do model
            Payment::create([
                'patient_id' => $patient->id,
                'user_id' => $this->user->id,
                'session_id' => $session?->id,
                'amount' => $amount,
                'payment_date' => $when,
                'due_date' => $status === 'Pago' ? null : $when,
                'payment_method' => $method,
                'status' => $status,
                'session_billing' => (bool) $session,
            ]);
        }
    }

    private function assessment(Patient $patient, Session $session, int $pain, string $complaint, string $history, string $goals): void
    {
        Assessment::create([
            'patient_id' => $patient->id,
            'user_id' => $this->user->id,
            'session_id' => $session->id,
            'chief_complaint' => $complaint,
            'duration' => '3 meses',
            'onset_type' => 'Gradual',
            'pain_level' => $pain,
            'previous_treatments' => 'Anti-inflamatórios e repouso, sem melhora significativa.',
            'medical_history' => $history,
            'medications' => 'Analgésico se necessário.',
            'surgery_procedures' => 'Nenhuma relevante.',
            'rom_findings' => 'Amplitude reduzida com dor ao final do movimento.',
            'strength_findings' => 'Força grau 4/5 nos grupos musculares envolvidos.',
            'balance_findings' => 'Sem alterações.',
            'gait_findings' => 'Marcha com leve compensação.',
            'adl_limitations' => 'Dificuldade em atividades que exigem esforço repetitivo.',
            'work_restrictions' => 'Evitar cargas pesadas.',
            'posture_assessment' => 'Anteriorização de cabeça e protrusão de ombros.',
            'palpation_findings' => 'Pontos de tensão em musculatura paravertebral.',
            'special_tests_results' => 'Testes provocativos positivos.',
            'edema_findings' => 'Ausente.',
            'skin_condition' => 'Íntegra.',
            'diagnostic_findings' => 'Exames de imagem compatíveis com o quadro clínico.',
            'diagnostic_notes' => null,
            'physio_diagnosis' => $complaint,
            'prognosis' => 'Bom, com adesão ao tratamento.',
            'functional_limitations' => 'Limitação moderada nas atividades diárias e de trabalho.',
            'treatment_goals_short' => 'Reduzir a dor e recuperar amplitude em 4 semanas.',
            'treatment_goals_long' => $goals,
            'recommended_frequency' => '2x por semana',
            'estimated_duration' => '8 semanas',
            'proposed_interventions' => 'Cinesioterapia, terapia manual e eletroterapia.',
            'home_exercise_plan' => 'Alongamentos e exercícios de fortalecimento 1x ao dia.',
            'additional_notes' => null,
        ]);
    }
}
