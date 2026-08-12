-- =============================================================================
-- Reset financeiro / operacional — banco: atendimento_domiciliar
--
-- APAGA :
--   payments          -> faturamentos e pagamentos (inclui session_billing = 1)
--   appointments      -> atendimentos / agendamentos
--   session_schedules -> grades de horário das sessões
--   sessions          -> sessões de tratamento (total_value, paid_value, etc.)
--
-- MANTÉM:
--   patients          -> cadastro de pacientes
--   assessments       -> avaliações de fisioterapia (session_id vira NULL)
--   addresses         -> endereços dos pacientes/clínicas
--   health_plans      -> catálogo de convênios
--   users, clinics
--
-- FAÇA BACKUP ANTES:
--   mysqldump -u sandro -p -h 127.0.0.1 -P 3380 atendimento_domiciliar \
--     > backup_pre_reset.sql
--
-- Uso:
--   mysql -u sandro -p -h 127.0.0.1 -P 3380 atendimento_domiciliar \
--     < database/sql/reset_financeiro.sql
--
-- Observação: usa DELETE (não TRUNCATE) de propósito.
--   1. TRUNCATE é rejeitado pelo MySQL em sessions/appointments — há FKs
--      apontando para elas.
--   2. Forçar com SET FOREIGN_KEY_CHECKS=0 impediria o ON DELETE SET NULL de
--      disparar, deixando assessments.session_id apontando para sessões
--      inexistentes.
--   3. DELETE remove também as linhas com soft delete (deleted_at preenchido),
--      que o Eloquent esconde mas continuam no banco.
-- =============================================================================

START TRANSACTION;

-- 1) Desvincula as avaliações das sessões que serão apagadas.
--    A FK (assessments.session_id -> sessions.id, ON DELETE SET NULL) já faria
--    isso automaticamente; explicitar deixa o script seguro mesmo se executado
--    com FOREIGN_KEY_CHECKS desabilitado.
UPDATE assessments
SET    session_id = NULL
WHERE  session_id IS NOT NULL;

-- 2) Faturamentos e pagamentos.
--    Apagar primeiro preserva a rastreabilidade: se sessions/appointments
--    fossem apagados antes, as FKs virariam NULL e perderíamos o vínculo.
DELETE FROM payments;

-- 3) Atendimentos / agendamentos.
DELETE FROM appointments;

-- 4) Grades de horário das sessões.
--    O CASCADE da FK já cobriria no passo 5; explícito é mais previsível.
DELETE FROM session_schedules;

-- 5) Sessões de tratamento.
DELETE FROM sessions;

-- 6) Reinicia a numeração (DELETE não reseta AUTO_INCREMENT).
ALTER TABLE payments          AUTO_INCREMENT = 1;
ALTER TABLE appointments      AUTO_INCREMENT = 1;
ALTER TABLE session_schedules AUTO_INCREMENT = 1;
ALTER TABLE sessions          AUTO_INCREMENT = 1;

COMMIT;
