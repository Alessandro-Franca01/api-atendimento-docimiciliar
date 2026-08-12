-- =============================================================================
-- Conferência do reset_financeiro.sql
--
-- Rodar DEPOIS do reset_financeiro.sql:
--   mysql -u sandro -p -h 127.0.0.1 -P 3380 atendimento_domiciliar \
--     < database/sql/reset_financeiro_verificacao.sql
--
-- Dica: rode este mesmo arquivo ANTES do reset também, para ter os números de
-- referência das tabelas que devem ser mantidas.
-- =============================================================================

-- 1) Contagem por tabela.
--    As 4 primeiras devem ser 0; as 4 últimas devem manter os valores de antes.
SELECT 'payments (apagar)'        AS tabela, COUNT(*) AS registros FROM payments
UNION ALL SELECT 'appointments (apagar)',      COUNT(*) FROM appointments
UNION ALL SELECT 'session_schedules (apagar)', COUNT(*) FROM session_schedules
UNION ALL SELECT 'sessions (apagar)',          COUNT(*) FROM sessions
UNION ALL SELECT 'patients (manter)',          COUNT(*) FROM patients
UNION ALL SELECT 'assessments (manter)',       COUNT(*) FROM assessments
UNION ALL SELECT 'addresses (manter)',         COUNT(*) FROM addresses
UNION ALL SELECT 'health_plans (manter)',      COUNT(*) FROM health_plans;

-- 2) Integridade: nenhuma avaliação pode apontar para sessão inexistente.
--    Esperado: 0.
SELECT COUNT(*) AS avaliacoes_orfas
FROM   assessments a
LEFT   JOIN sessions s ON s.id = a.session_id
WHERE  a.session_id IS NOT NULL
AND    s.id IS NULL;

-- 3) Financeiro zerado. Esperado: NULL (nenhuma linha) ou 0.
SELECT SUM(amount) AS receita_total,
       COUNT(*)    AS qtd_pagamentos,
       SUM(session_billing = 1) AS faturamentos_automaticos
FROM   payments;

-- 4) Pacientes preservados com suas avaliações (amostra).
SELECT p.id,
       p.name,
       COUNT(a.id) AS avaliacoes
FROM   patients p
LEFT   JOIN assessments a ON a.patient_id = p.id AND a.deleted_at IS NULL
WHERE  p.deleted_at IS NULL
GROUP  BY p.id, p.name
ORDER  BY p.id
LIMIT  20;
