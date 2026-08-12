# Reset Financeiro — Instruções de Execução

Runbook para zerar os dados transacionais do sistema (faturamentos, atendimentos, agendamentos) **preservando o cadastro clínico**.

> ⚠️ **Operação destrutiva e irreversível sem backup.** Leia o runbook inteiro antes de rodar qualquer comando.

---

## O que o reset faz

| Tabela | Ação | Conteúdo |
|---|---|---|
| `payments` | 🔴 **APAGA** | Pagamentos e faturamentos (inclui os automáticos, `session_billing = 1`) |
| `appointments` | 🔴 **APAGA** | Atendimentos / agendamentos |
| `session_schedules` | 🔴 **APAGA** | Grades de horário das sessões |
| `sessions` | 🔴 **APAGA** | Sessões de tratamento (`total_value`, `paid_value`, contadores) |
| `patients` | 🟢 mantém | Cadastro de pacientes |
| `assessments` | 🟢 mantém | Avaliações de fisioterapia (o `session_id` vira `NULL`) |
| `addresses` | 🟢 mantém | Endereços |
| `health_plans` | 🟢 mantém | Catálogo de convênios |
| `users`, `clinics` | 🟢 mantém | Usuários e clínicas |

**Resultado esperado:** dashboard com R$ 0,00 de receita, agenda vazia, nenhuma sessão ativa. Pacientes e avaliações intactos e prontos para receber novas sessões.

### Por que as avaliações sobrevivem

Em `database/migrations/2026_02_05_000000_create_assessments_table.php:18`:

```php
$table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('set null');
```

A FK é `SET NULL`, não `CASCADE` — apagar a sessão apenas anula o vínculo e a avaliação permanece. Avaliação sem sessão já é um estado válido no sistema (`AssessmentService` cria com `'session_id' => $data['session_id'] ?? null`, e a rota é `patients.assessments`, nunca aninhada em sessão).

### Por que `DELETE` e não `TRUNCATE`

1. `TRUNCATE` é **rejeitado** pelo MariaDB/MySQL em `sessions` e `appointments` — existem FKs apontando para elas.
2. Forçar com `SET FOREIGN_KEY_CHECKS = 0` **impediria o `SET NULL` de disparar**, deixando `assessments.session_id` apontando para sessões inexistentes — exatamente o que queremos evitar.
3. `DELETE` também remove as linhas com *soft delete* (`deleted_at` preenchido), que o Eloquent esconde mas continuam ocupando o banco.

O `AUTO_INCREMENT` é reiniciado manualmente no script, já que `DELETE` não faz isso.

---

## Arquivos

| Arquivo | Função |
|---|---|
| `reset_financeiro.sql` | O script de limpeza (roda dentro de uma transação) |
| `reset_financeiro_verificacao.sql` | Conferência — rode **antes e depois** |
| `README.md` | Este runbook |

---

## Pré-requisitos

O banco roda em um **container Docker**:

```
Container : db_atendimento_domiciliar
Imagem    : mariadb:latest
Database  : atendimento_domiciliar
Usuário   : sandro  (senha: root)  |  root (senha: root)
Porta     : host 3301 -> container 3306
```

### ⚠️ Divergência de porta

O `.env` do projeto tem `DB_PORT=3380`, mas o container publica a porta **3301**. Antes de rodar o reset, confirme qual instância a API está usando de verdade — rodar o script no banco errado apaga dados do sistema errado.

```powershell
# Ver a porta configurada na aplicação
Select-String -Path .env -Pattern "^DB_"

# Ver a porta publicada pelo container
docker inspect db_atendimento_domiciliar --format "{{json .HostConfig.PortBindings}}"
```

Se divergirem, ajuste o `.env` (ou o mapeamento do container) **antes** de continuar.

### Subir o container

O container costuma ficar parado. Inicie antes de qualquer comando:

```powershell
docker start db_atendimento_domiciliar

# Confirmar que subiu e aceita conexão
docker exec db_atendimento_domiciliar mariadb -u root -proot -e "SELECT 1;"
```

> Nota: na imagem `mariadb:11.x` os binários nativos são `mariadb` e `mariadb-dump`. Os nomes antigos (`mysql`, `mysqldump`) ainda funcionam como alias, mas emitem aviso de depreciação. Este runbook usa os nomes nativos.

---

## Passo a passo

### 1. Backup (obrigatório)

Gera o dump **dentro** do container e copia para fora — evita problemas de encoding do redirecionamento do PowerShell, que pode inserir BOM e corromper o arquivo.

```powershell
$stamp = Get-Date -Format "yyyyMMdd_HHmmss"

docker exec db_atendimento_domiciliar sh -c `
  "mariadb-dump -u root -proot --single-transaction atendimento_domiciliar > /tmp/backup.sql"

docker cp db_atendimento_domiciliar:/tmp/backup.sql ".\backup_pre_reset_$stamp.sql"

# Confirmar que o arquivo não está vazio
Get-Item ".\backup_pre_reset_$stamp.sql" | Select-Object Name, Length
```

O arquivo deve ter alguns MB. **Se vier com 0 bytes, pare aqui** — o dump falhou e você não tem rede de segurança.

### 2. Contagens antes

Guarde estes números para comparar depois:

```powershell
docker cp .\database\sql\reset_financeiro_verificacao.sql db_atendimento_domiciliar:/tmp/verifica.sql
docker exec db_atendimento_domiciliar sh -c "mariadb -u root -proot atendimento_domiciliar < /tmp/verifica.sql"
```

Anote as contagens de `patients`, `assessments`, `addresses` e `health_plans` — elas **não podem mudar**.

### 3. Rodar o reset

```powershell
docker cp .\database\sql\reset_financeiro.sql db_atendimento_domiciliar:/tmp/reset.sql
docker exec db_atendimento_domiciliar sh -c "mariadb -u root -proot atendimento_domiciliar < /tmp/reset.sql"
```

Sem saída = sucesso. O script roda dentro de `START TRANSACTION ... COMMIT`, então qualquer erro no meio aborta tudo sem deixar estado parcial.

### 4. Conferir

```powershell
docker exec db_atendimento_domiciliar sh -c "mariadb -u root -proot atendimento_domiciliar < /tmp/verifica.sql"
```

Critérios de aceite:

- `payments`, `appointments`, `session_schedules`, `sessions` → **0**
- `patients`, `assessments`, `addresses`, `health_plans` → **iguais ao passo 2**
- `avaliacoes_orfas` → **0**
- `receita_total` → **NULL** (nenhuma linha)
- A última query lista os pacientes com a contagem de avaliações preservada

### 5. Limpar os arquivos temporários do container

```powershell
docker exec db_atendimento_domiciliar rm -f /tmp/reset.sql /tmp/verifica.sql /tmp/backup.sql
```

---

## Alternativa: Git Bash / cliente local

Se você tiver o cliente `mysql`/`mariadb` instalado no host (hoje **não está no PATH**), dá para rodar direto — o redirecionamento `<` funciona normalmente no Bash:

```bash
cd api-atendimento-domiciliar

mysqldump -u sandro -p -h 127.0.0.1 -P 3301 --single-transaction \
  atendimento_domiciliar > backup_pre_reset.sql

mysql -u sandro -p -h 127.0.0.1 -P 3301 atendimento_domiciliar \
  < database/sql/reset_financeiro.sql

mysql -u sandro -p -h 127.0.0.1 -P 3301 atendimento_domiciliar \
  < database/sql/reset_financeiro_verificacao.sql
```

> ⚠️ **No PowerShell 5.1 o operador `<` não existe** — é erro de sintaxe. Use a abordagem com `docker cp` acima, ou `Get-Content arquivo.sql -Raw | docker exec -i ...`.

---

## Rollback

Se algo der errado, restaure o backup do passo 1:

```powershell
docker cp ".\backup_pre_reset_<stamp>.sql" db_atendimento_domiciliar:/tmp/restore.sql
docker exec db_atendimento_domiciliar sh -c "mariadb -u root -proot atendimento_domiciliar < /tmp/restore.sql"
```

O dump do `mariadb-dump` inclui `DROP TABLE IF EXISTS` + `CREATE TABLE`, então a restauração sobrescreve o estado atual por completo.

---

## Verificação na aplicação

Depois do reset, suba os dois projetos e confira o comportamento ponta a ponta:

```bash
# Terminal 1
cd api-atendimento-domiciliar && php artisan serve

# Terminal 2
cd Fisio-Gestor && npm run dev
```

1. **Dashboard** — receita R$ 0,00, nenhum agendamento do dia, nenhuma sessão ativa.
2. **Lista de Pacientes** — todos presentes; status financeiro "Em dia", `total_to_pay` e `total_paid` zerados. Esses valores são *accessors* calculados em `app/Models/Patient.php:65-95` a partir de `payments()`, então se recalculam sozinhos — não há coluna a resetar.
3. **Paciente → Avaliações** (`/patient/:id/assessment`) — a lista carrega e o `AssessmentDetail` abre normalmente, agora sem sessão vinculada.
4. **Fluxo de escrita** — criar uma nova sessão para um paciente existente. O `SessionService` gera os `appointments` a partir da grade de horários; confirme que aparecem na agenda e que os IDs começam em 1.
5. **Testes** — `php artisan test --filter=SessionBillingTest` deve continuar verde (a suíte usa banco próprio, não é afetada pelo reset).

---

## Observações

- **Anexos órfãos em disco.** `appointments.attachments` e `appointments.resources` são colunas JSON. Se houver arquivos gravados em `storage/`, eles ficam órfãos depois do reset. Não afeta o funcionamento do sistema; limpe manualmente se quiser recuperar espaço.

- **Colisão de nome na tabela `sessions`.** O `.env` tem `SESSION_DRIVER=database`, o que aponta o driver de sessão do Laravel para a tabela `sessions` — que neste projeto é a tabela de **domínio** (sessões de tratamento). A migration de sessão do framework está comentada em `0001_01_01_000000_create_users_table.php`. Como a API é stateless via Sanctum, nada é gravado ali na prática, mas vale saber: `sessions` aqui é dado clínico, não cache de sessão web.

- **Contadores denormalizados.** `sessions.completed_appointments` e `sessions.paid_value` são mantidos por hooks `booted()` em `Appointment` e `Payment`, que **não disparam** em SQL cru. Como as linhas de `sessions` são apagadas por completo, não sobra nenhum contador desatualizado.

- **Não existe tabela de faturamento.** Todo o financeiro vive em `payments`: a coluna `session_billing` marca os faturamentos automáticos gerados por `SessionService::checkAndBillSession()`, e o `DashboardController` calcula a receita em tempo real. Não há tabela de receita persistida — zerar `payments` zera o financeiro.
