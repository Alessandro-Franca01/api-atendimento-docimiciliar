# FisioGestor API - Laravel

API completa para o sistema de gerenciamento de fisioterapeutas FisioGestor.

## 🚀 Instalação

### Pré-requisitos
- PHP 8.2 ou superior
- Composer
- MySQL 8.0 ou superior
- Laravel 12

### Passo a passo

1. **Clone o repositório e instale as dependências**
```bash
composer install
```

2. **Configure o arquivo .env**
```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` e configure o banco de dados:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fisiogestor
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1
```

3. **Instale o Laravel Sanctum**
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

4. **Execute as migrations e seeders**
```bash
php artisan migrate:fresh --seed
```

5. **Inicie o servidor**
```bash
php artisan serve
```

A API estará disponível em `http://localhost:8000/api`

## 📋 Credenciais de Teste

**Email:** dr.carlos@fisiogestor.com  
**Senha:** password

## 🔐 Autenticação

A API usa Laravel Sanctum para autenticação via tokens.

### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "dr.carlos@fisiogestor.com",
  "password": "password"
}
```

**Resposta:**
```json
{
  "user": { ... },
  "token": "1|laravel_sanctum_token..."
}
```

### Usar o token nas requisições
```http
Authorization: Bearer {seu_token}
```

### Logout
```http
POST /api/logout
Authorization: Bearer {seu_token}
```

## 📚 Endpoints da API

### Dashboard
```http
GET /api/dashboard
```
Retorna estatísticas do dashboard (receitas, atendimentos próximos, pendências).

---

### Pacientes

#### Listar pacientes
```http
GET /api/patients?search=ana&status=Ativo
```

#### Criar paciente
```http
POST /api/patients
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@email.com",
  "phone": "(11) 98765-4321",
  "cpf": "123.456.789-00",
  "birth_date": "1990-01-15",
  "age": 34,
  "emergency_contact_name": "Maria Silva",
  "emergency_contact_phone": "(11) 99999-8888",
  "status": "Ativo",
  "notes": "Observações do paciente",
  "addresses": [
    {
      "type": "Residencial",
      "street": "Rua Exemplo",
      "number": "123",
      "complement": "Apto 45",
      "neighborhood": "Centro",
      "city": "São Paulo",
      "state": "SP",
      "zip_code": "01234-567",
      "is_primary": true
    }
  ]
}
```

#### Visualizar paciente
```http
GET /api/patients/{id}
```

#### Atualizar paciente
```http
PUT /api/patients/{id}
```

#### Excluir paciente
```http
DELETE /api/patients/{id}
```

#### Resumo financeiro do paciente
```http
GET /api/patients/{id}/financial
```

---

### Endereços

#### Adicionar endereço ao paciente
```http
POST /api/patients/{patient_id}/addresses
Content-Type: application/json

{
  "type": "Residencial",
  "street": "Rua das Flores",
  "number": "456",
  "neighborhood": "Jardim",
  "city": "São Paulo",
  "state": "SP",
  "zip_code": "12345-678",
  "is_primary": true
}
```

#### Atualizar endereço
```http
PUT /api/addresses/{id}
```

#### Excluir endereço
```http
DELETE /api/addresses/{id}
```

---

### Atendimentos

#### Listar atendimentos
```http
GET /api/appointments?date=2024-08-15&status=Pendente&patient_id=1
```

#### Criar atendimento
```http
POST /api/appointments
Content-Type: application/json

{
  "patient_id": 1,
  "session_id": 1,
  "date": "2024-08-15",
  "scheduled_time": "10:00",
  "type": "Fisioterapia",
  "status": "Pendente",
  "observations": "Paciente com dor lombar"
}
```

#### Visualizar atendimento
```http
GET /api/appointments/{id}
```

#### Atualizar atendimento
```http
PUT /api/appointments/{id}
Content-Type: application/json

{
  "status": "Realizado",
  "session_notes": "Sessão realizada com sucesso"
}
```

#### Check-in
```http
POST /api/appointments/{id}/check-in
```

#### Check-out
```http
POST /api/appointments/{id}/check-out
```

#### Excluir atendimento
```http
DELETE /api/appointments/{id}
```

---

### Sessões

#### Listar sessões
```http
GET /api/sessions?patient_id=1&status=Ativa
```

#### Criar sessão
```http
POST /api/sessions
Content-Type: application/json

{
  "patient_id": 1,
  "title": "Tratamento Fisioterapia",
  "total_appointments": 10,
  "total_value": 1500.00,
  "start_date": "2024-08-01",
  "observations": "Tratamento para dor nas costas",
  "schedules": [
    {
      "day_of_week": "Segunda-feira",
      "time": "10:00"
    },
    {
      "day_of_week": "Quarta-feira",
      "time": "14:00"
    }
  ]
}
```

#### Visualizar sessão
```http
GET /api/sessions/{id}
```

#### Atualizar sessão
```http
PUT /api/sessions/{id}
```

#### Excluir sessão
```http
DELETE /api/sessions/{id}
```

---

### Pagamentos

#### Listar pagamentos
```http
GET /api/payments?patient_id=1&status=Pago
```

#### Criar pagamento
```http
POST /api/payments
Content-Type: application/json

{
  "patient_id": 1,
  "session_id": 1,
  "amount": 150.00,
  "payment_date": "2024-08-15",
  "payment_method": "Pix",
  "status": "Pago",
  "notes": "Pagamento da sessão 5"
}
```

#### Visualizar pagamento
```http
GET /api/payments/{id}
```

#### Atualizar pagamento
```http
PUT /api/payments/{id}
```

#### Excluir pagamento
```http
DELETE /api/payments/{id}
```

---

## 🗂️ Estrutura do Banco de Dados

### Tabelas principais:
- **users** - Fisioterapeutas/usuários do sistema
- **patients** - Pacientes
- **addresses** - Endereços dos pacientes
- **sessions** - Sessões de tratamento
- **session_schedules** - Horários fixos das sessões
- **appointments** - Atendimentos/consultas
- **payments** - Pagamentos

### Relacionamentos:
- User → Patients (1:N)
- Patient → Addresses (1:N)
- Patient → Sessions (1:N)
- Patient → Appointments (1:N)
- Session → Appointments (1:N)
- Session → SessionSchedules (1:N)
- Session → Payments (1:N)
- Appointment → Payment (1:1)

## 🔧 Configuração CORS

Para permitir requisições do frontend, adicione no arquivo `config/cors.php`:

```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

## 📝 Notas importantes

1. **Soft Deletes**: As entidades principais (Patient, Session, Appointment, Payment) usam soft deletes
2. **Autorização**: Todos os endpoints verificam se o recurso pertence ao usuário autenticado
3. **Paginação**: Listas retornam 15 itens por padrão
4. **Cálculos automáticos**: Status financeiro e contadores são calculados automaticamente
5. **Eventos**: Ao marcar atendimento como "Realizado", incrementa automaticamente o contador da sessão

## 🧪 Testes

Para executar os testes (quando implementados):
```bash
php artisan test
```

## 📦 Comandos úteis

```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rotas disponíveis
php artisan route:list

# Criar nova migration
php artisan make:migration create_table_name

# Criar novo model
php artisan make:model ModelName -m

# Criar novo controller
php artisan make:controller Api/ControllerName
```

## 🤝 Integração com o Frontend

O frontend React já está configurado para consumir esta API. Certifique-se de:

1. Configurar a base URL da API no frontend
2. Salvar o token de autenticação após o login
3. Incluir o token em todas as requisições autenticadas
4. Tratar erros de autenticação (401) redirecionando para login

Exemplo de configuração no frontend:
```javascript
// axios config
axios.defaults.baseURL = 'http://localhost:8000/api';
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

## 📄 Licença

MIT
