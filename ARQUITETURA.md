# 📐 Documentação Arquitetural - Sistema PagTesouro DGOM

> **Guia para Novos Desenvolvedores**
>
> Este documento apresenta uma visão completa da arquitetura do sistema, fluxos de dados, e organização do código.

---

## 📋 Índice

1. [Visão Geral do Sistema](#-visão-geral-do-sistema)
2. [Mapa Mental do Sistema](#-mapa-mental-do-sistema)
3. [Fluxo de Request/Response](#-fluxo-de-requestresponse)
4. [Sequência de Chamadas Entre Camadas](#-sequência-de-chamadas-entre-camadas)
5. [Mapeamento de Domínios por Arquivo](#-mapeamento-de-domínios-por-arquivo)
6. [Localização dos Componentes](#-localização-dos-componentes)
7. [Padrões Arquiteturais](#-padrões-arquiteturais)
8. [Pontos de Acoplamento](#-pontos-de-acoplamento)
9. [Recomendações de Refatoração](#-recomendações-de-refatoração)

---

## 🎯 Visão Geral do Sistema

### Propósito
Sistema intermediário (middleware) entre uma aplicação frontend e a API PagTesouro do Governo Federal, responsável por:
- Criar solicitações de pagamento (GRU - Guia de Recolhimento da União)
- Atualizar status de pagamentos
- Integrar com sistema SINGRA para atualização de saldos

### Stack Tecnológica
- **Runtime**: Node.js
- **Framework Web**: Express.js
- **Banco de Dados**: PostgreSQL 12
- **Protocolo**: HTTPS (porta 3000)
- **Proxy**: proxy-1dn.mb:6060

### Arquitetura Atual
**Tipo**: Monolítico procedural, arquivo único
**Complexidade**: Baixa (588 linhas em server.js)
**Padrão**: Script procedural sem separação de camadas

---

## 🧠 Mapa Mental do Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                     SISTEMA PAGTESOURO DGOM                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
   ┌─────────┐         ┌──────────┐         ┌──────────┐
   │ FRONTEND│         │  SERVER  │         │ SISTEMAS │
   │  (HTML) │         │ (Node.js)│         │ EXTERNOS │
   └─────────┘         └──────────┘         └──────────┘
        │                     │                     │
        │                     │                     │
        │              ┌──────┴──────┐             │
        │              │             │             │
        │              ▼             ▼             │
        │      ┌──────────┐   ┌──────────┐        │
        │      │  ROTAS   │   │MIDDLEWARE│        │
        │      │ /handle  │   │ Security │        │
        │      │ /update  │   │   CORS   │        │
        │      └──────────┘   └──────────┘        │
        │              │                           │
        │              ▼                           │
        │      ┌──────────────┐                   │
        │      │   BUSINESS   │                   │
        │      │    LOGIC     │                   │
        │      │ (inline)     │                   │
        │      └──────────────┘                   │
        │              │                           │
        │      ┌───────┴────────┐                │
        │      │                │                 │
        │      ▼                ▼                 │
        │  ┌────────┐    ┌──────────┐           │
        │  │DATABASE│    │ EXTERNAL │           │
        └──│  (PG)  │    │   APIs   │───────────┘
           └────────┘    └──────────┘
                               │
                    ┌──────────┴───────────┐
                    │                      │
                    ▼                      ▼
             ┌─────────────┐      ┌──────────────┐
             │ PAGTESOURO  │      │    SINGRA    │
             │  (Tesouro   │      │  (Saldo de   │
             │  Nacional)  │      │   Usuários)  │
             └─────────────┘      └──────────────┘
```

### Componentes Principais

1. **Frontend**: `DGOM TABEAS` (HTML)
   - Interface para entrada de dados de pagamento

2. **Servidor Express**: `server.js`
   - Endpoint `/handle`: Criar pagamentos
   - Endpoint `/update`: Atualizar status

3. **Banco de Dados**: PostgreSQL
   - Schema: `pagtesouro`
   - Tabela principal: `tb_pgto`

4. **APIs Externas**:
   - **PagTesouro**: API do Tesouro Nacional
   - **SINGRA**: Sistema interno de gestão de usuários

---

## 🔄 Fluxo de Request/Response

### Fluxo 1: Criação de Pagamento (POST /handle)

```
┌─────────┐
│ Cliente │
└────┬────┘
     │ POST /handle
     │ { cpf, valor, servico, ... }
     ▼
┌────────────────────────────────────────────────────────┐
│                    SERVER.JS                           │
├────────────────────────────────────────────────────────┤
│                                                        │
│  1. RECEBIMENTO                                        │
│     └─ Express middleware (body-parser)                │
│     └─ CORS, Security Headers                          │
│                                                        │
│  2. VALIDAÇÃO                                          │
│     └─ Valida campos obrigatórios                      │
│     └─ Verifica estrutura do payload                   │
│                                                        │
│  3. CONSULTA BANCO (SELECT)                            │
│     └─ Busca sequencial de referência                  │
│        SELECT MAX(CD_REF_SEQ) FROM tb_pgto             │
│        WHERE cd_cpf = ? AND cd_om = ?                  │
│     ◄── PostgreSQL                                     │
│                                                        │
│  4. GERA CÓDIGO DE REFERÊNCIA                          │
│     └─ Função: montaref(dados, seq)                    │
│     └─ Formato: AAAA.SSSS.CCCC.OOOO.NNNNN.DD           │
│                                                        │
│  5. CRIPTOGRAFA DADOS SENSÍVEIS                        │
│     └─ AES-128-CBC                                     │
│     └─ Criptografa: nome, CPF                          │
│                                                        │
│  6. CHAMA API PAGTESOURO                               │
│     └─ POST api/gru/solicitacao-pagamento              │
│     └─ Headers: Authorization Bearer {token}           │
│     └─ Proxy: proxy-1dn.mb:6060                        │
│     ◄─┐                                                │
│       │                                                │
│       ▼                                                │
│  ┌──────────────────────┐                             │
│  │   API PAGTESOURO     │                             │
│  │  (Tesouro Nacional)  │                             │
│  └──────────────────────┘                             │
│       │ { idPagamento, situacao, ... }                │
│       └──►                                             │
│                                                        │
│  7. GRAVA BANCO (INSERT)                               │
│     └─ INSERT INTO tb_pgto (31 colunas)                │
│     └─ Armazena todos os dados do pagamento            │
│     ◄── PostgreSQL                                     │
│                                                        │
│  8. RETORNA RESPOSTA                                   │
│     └─ JSON com ID e status do pagamento               │
│                                                        │
└────────────────────────────────────────────────────────┘
     │
     │ { idPagamento, situacao, urlPagamento, ... }
     ▼
┌─────────┐
│ Cliente │
└─────────┘
```

**Tempo de Resposta**: ~2-5 segundos (depende do PagTesouro)

---

### Fluxo 2: Atualização de Status (POST /update)

```
┌─────────┐
│ Cliente │
└────┬────┘
     │ POST /update
     │ { idPagamento }
     ▼
┌────────────────────────────────────────────────────────┐
│                    SERVER.JS                           │
├────────────────────────────────────────────────────────┤
│                                                        │
│  1. CONSULTA API PAGTESOURO                            │
│     └─ GET api/gru/pagamentos/{id}                     │
│     ◄─┐                                                │
│       │                                                │
│       ▼                                                │
│  ┌──────────────────────┐                             │
│  │   API PAGTESOURO     │                             │
│  └──────────────────────┘                             │
│       │ { status, valor, PSP, ... }                   │
│       └──►                                             │
│                                                        │
│  2. ATUALIZA BANCO (UPDATE)                            │
│     └─ UPDATE tb_pgto SET                              │
│        ds_situacao, vr_pago, ds_nomepsp, ...           │
│        WHERE id_pgto = ?                               │
│     ◄── PostgreSQL                                     │
│                                                        │
│  3. VERIFICA CATEGORIA                                 │
│     └─ Se categoria = "CCIM" E status = "PAGO"        │
│        └─ Notifica SINGRA ───┐                        │
│                              │                         │
│  4. NOTIFICA SINGRA (condicional)                      │
│     └─ POST api-singra.dabm.mb/pagamento  ◄───────────┘
│     └─ Auth: Basic (admin:pwssingra)                   │
│     ◄─┐                                                │
│       │                                                │
│       ▼                                                │
│  ┌──────────────────────┐                             │
│  │    API SINGRA        │                             │
│  │  (Atualiza saldo)    │                             │
│  └──────────────────────┘                             │
│       │ { success }                                    │
│       └──►                                             │
│                                                        │
│  5. MARCA FLAG SINGRA                                  │
│     └─ UPDATE tb_pgto SET singra_ok = 1                │
│        WHERE id_pgto = ?                               │
│     ◄── PostgreSQL                                     │
│                                                        │
│  6. RETORNA SUCESSO                                    │
│     └─ { success: true }                               │
│                                                        │
└────────────────────────────────────────────────────────┘
     │
     │ { success: true }
     ▼
┌─────────┐
│ Cliente │
└─────────┘
```

**Tempo de Resposta**: ~1-3 segundos

---

## 🔗 Sequência de Chamadas Entre Camadas

### Problema Atual: **NÃO HÁ CAMADAS SEPARADAS**

O sistema atual é **procedural monolítico**. Tudo ocorre em uma única função de rota.

#### Estrutura Atual (Anti-padrão)

```javascript
// server.js - Linhas 108-310
app.post('/handle', async (request, response) => {
  // ❌ Tudo misturado em uma função:

  // Validação
  if (!request.body.cpf) { ... }

  // Acesso a Dados
  const pool = new Pool({ ... })
  pool.query("SELECT ...", ...)

  // Lógica de Negócio
  var codigoReferencia = montaref(value, seq)

  // Criptografia
  var cipher = crypto.createCipheriv(...)

  // Chamada API Externa
  await axios.post(url, dados, config)

  // Mais Acesso a Dados
  pool.query("INSERT ...", ...)

  // Tratamento de Erros
  catch (error) { ... }
})
```

#### Estrutura Ideal (Recomendada)

```
┌──────────────────────────────────────────────────┐
│                   CAMADA DE APRESENTAÇÃO          │
├──────────────────────────────────────────────────┤
│  ▸ routes/payment.routes.js                      │
│    └─ Define rotas e delega para controllers     │
└──────────────────────────────────────────────────┘
                      │
                      ▼
┌──────────────────────────────────────────────────┐
│                   CAMADA DE CONTROLE              │
├──────────────────────────────────────────────────┤
│  ▸ controllers/paymentController.js              │
│    ├─ createPayment(req, res)                    │
│    └─ updatePaymentStatus(req, res)              │
│       └─ Valida entrada                          │
│       └─ Chama services                          │
│       └─ Formata resposta                        │
└──────────────────────────────────────────────────┘
                      │
                      ▼
┌──────────────────────────────────────────────────┐
│                   CAMADA DE SERVIÇOS              │
├──────────────────────────────────────────────────┤
│  ▸ services/paymentService.js                    │
│    ├─ createPaymentRequest(data)                 │
│    │   └─ Lógica de negócio                      │
│    │   └─ Orquestra repositories e integrações   │
│    └─ updatePaymentStatus(id)                    │
│                                                   │
│  ▸ services/referenceCodeService.js              │
│    └─ generateReferenceCode(data, seq)           │
│                                                   │
│  ▸ services/encryptionService.js                 │
│    └─ encryptSensitiveData(data)                 │
└──────────────────────────────────────────────────┘
                      │
            ┌─────────┴─────────┐
            ▼                   ▼
┌───────────────────────┐  ┌──────────────────────┐
│  CAMADA DE INTEGRAÇÃO │  │  CAMADA DE DADOS     │
├───────────────────────┤  ├──────────────────────┤
│ ▸ integrations/       │  │ ▸ repositories/      │
│   pagTesouro.js       │  │   paymentRepo.js     │
│   └─ API calls        │  │   └─ CRUD operations │
│                       │  │                      │
│ ▸ integrations/       │  │ ▸ models/            │
│   singra.js           │  │   payment.model.js   │
│   └─ API calls        │  │   └─ Schema          │
└───────────────────────┘  └──────────────────────┘
```

#### Fluxo Recomendado (Call Stack)

```
1. HTTP Request
   ↓
2. Express Middleware (CORS, Security, Body Parser)
   ↓
3. Router (routes/payment.routes.js)
   ↓
4. Controller (controllers/paymentController.js)
   ├─ Validação de entrada
   ├─ Parsing de dados
   └─ Chamada ao Service
   ↓
5. Service (services/paymentService.js)
   ├─ Lógica de negócio
   ├─ Orquestração
   │  ├─► Repository (busca sequencial)
   │  ├─► ReferenceCodeService (gera código)
   │  ├─► EncryptionService (criptografa)
   │  ├─► PagTesourosIntegration (API externa)
   │  └─► Repository (salva pagamento)
   └─ Retorna resultado
   ↓
6. Controller (recebe resultado)
   ├─ Formata resposta HTTP
   └─ Define status code
   ↓
7. HTTP Response
```

---

## 📁 Mapeamento de Domínios por Arquivo

### Estrutura Atual

| Arquivo | Linhas | Responsabilidade | Domínios Cobertos |
|---------|--------|------------------|-------------------|
| **server.js** | 588 | Tudo | ▸ Configuração<br>▸ Middleware<br>▸ Rotas<br>▸ Controllers<br>▸ Services<br>▸ Repositories<br>▸ Integrações<br>▸ Criptografia<br>▸ Logging<br>▸ Error Handling |
| **pgt.js** | ~588 | Backup/Dev version | Mesmos domínios do server.js |
| **papem_test.js** | ~150 | Testes manuais | ▸ Script de teste para PAPEM |
| **banco-dgom.md** | - | Documentação | ▸ Schema do banco |
| **DGOM TABEAS** | - | Frontend | ▸ Interface HTML |

### Estrutura Recomendada

```
dgom_pagtesouro/
│
├── src/
│   ├── config/                    # ⚙️ Configurações
│   │   ├── database.js            # Conexão PostgreSQL
│   │   ├── environment.js         # Variáveis de ambiente
│   │   ├── ssl.js                 # Certificados SSL
│   │   └── tokens.js              # Tokens de API (via env)
│   │
│   ├── routes/                    # 🛣️ Rotas HTTP
│   │   ├── index.js               # Exporta todas as rotas
│   │   └── payment.routes.js      # Rotas de pagamento
│   │
│   ├── controllers/               # 🎮 Controllers
│   │   └── paymentController.js   # Controla /handle e /update
│   │
│   ├── services/                  # 💼 Lógica de Negócio
│   │   ├── paymentService.js      # Orquestra criação/atualização
│   │   ├── referenceCodeService.js # Gera código de referência
│   │   └── encryptionService.js   # Criptografia AES
│   │
│   ├── repositories/              # 🗄️ Acesso a Dados
│   │   └── paymentRepository.js   # CRUD para tb_pgto
│   │
│   ├── integrations/              # 🔌 APIs Externas
│   │   ├── pagTesourosClient.js   # Cliente PagTesouro API
│   │   └── singraClient.js        # Cliente SINGRA API
│   │
│   ├── middleware/                # 🛡️ Middleware Express
│   │   ├── security.js            # HSTS, X-Frame, Cache
│   │   ├── errorHandler.js        # Tratamento global de erros
│   │   └── requestLogger.js       # Log de requests
│   │
│   ├── models/                    # 📦 Modelos de Dados
│   │   └── payment.model.js       # Schema de pagamento
│   │
│   ├── utils/                     # 🔧 Utilitários
│   │   ├── logger.js              # Winston/Pino logger
│   │   └── validators.js          # Validação de dados
│   │
│   └── app.js                     # 🚀 Bootstrap da aplicação
│
├── tests/                         # 🧪 Testes
│   ├── unit/
│   ├── integration/
│   └── e2e/
│
├── docs/                          # 📚 Documentação
│   ├── ARQUITETURA.md
│   ├── banco-dgom.md
│   └── API.md
│
├── .env.example                   # Exemplo de variáveis de ambiente
├── .gitignore
├── package.json
└── server.js                      # Entry point (importa src/app.js)
```

---

## 📍 Localização dos Componentes

### 🔐 Authentication (Auth)

**Localização Atual**: ❌ **NÃO EXISTE**

O servidor **não possui autenticação** para proteger os endpoints `/handle` e `/update`.

**Onde deveria estar**:
```javascript
// src/middleware/auth.js
module.exports = {
  authenticateJWT: (req, res, next) => { ... },
  authorizeRole: (roles) => (req, res, next) => { ... }
}

// Uso nas rotas:
router.post('/handle',
  authenticateJWT,
  authorizeRole(['admin', 'operator']),
  paymentController.create
)
```

**Autenticação de APIs Externas**:

| Sistema | Método | Localização no Código |
|---------|--------|----------------------|
| **PagTesouro** | Bearer Token | server.js:203-206, 248-249 |
| **SINGRA** | Basic Auth | server.js:411-414 |

```javascript
// server.js - Linha 203-206
var token = tokenAcesso;  // Token padrão
if (value.cat_servico == "CCCPM") token = tokenAcessoCCCPM;
if (value.cat_servico == "CCCPM2") token = tokenAcessoCCCPM2;
if (value.cat_servico == "PAPEM") token = tokenAcessoPAPEM;

// server.js - Linha 248-249 (PagTesouro)
headers: {
  'Authorization': 'Bearer ' + token,
  'Proxy-Autorization': aut
}

// server.js - Linha 411-414 (SINGRA)
headers: {
  'Authorization': 'Basic ' +
    new Buffer.from('admin:pwssingra').toString('base64')
}
```

---

### 📝 Logging (Log)

**Localização Atual**: server.js:79-81

```javascript
function geralog(texto) {
  console.log(new Date().toLocaleString() + " - " + texto);
}
```

**Chamadas de Log**:
- server.js:114 - " Dados para GRU Recebidos!"
- server.js:122 - "Consulta para montagem do sequencial..."
- server.js:217 - " Emitindo POST-REQUEST para..."
- server.js:274 - "Resposta PagTesouro recebida!"
- server.js:282 - "Acesso: X - Resposta com Erro!"
- server.js:185 - "Erro no registro! (POSTGRES)"
- server.js:374 - "Enviando POST-REQUEST para SINGRA..."

**Problemas**:
- ❌ Não há níveis de log (debug, info, warn, error)
- ❌ Não há arquivo de log (apenas console)
- ❌ Não há rotação de logs
- ❌ Não há log estruturado (JSON)
- ❌ Difícil de filtrar e buscar logs

**Onde deveria estar**:
```javascript
// src/utils/logger.js
const winston = require('winston')

const logger = winston.createLogger({
  level: 'info',
  format: winston.format.json(),
  transports: [
    new winston.transports.File({ filename: 'error.log', level: 'error' }),
    new winston.transports.File({ filename: 'combined.log' })
  ]
})

module.exports = logger

// Uso:
logger.info('Dados para GRU Recebidos', { cpf: '***', valor: 100 })
logger.error('Erro no registro', { error: err.message, stack: err.stack })
```

---

### ⚙️ Configuration (Config)

**Localização Atual**: server.js:84-105 (hardcoded)

```javascript
// URLs dos ambientes
var hmg_ender = 'https://valpagtesouro.tesouro.gov.br/api/gru/';
var prd_ender = 'https://pagtesouro.tesouro.gov.br/api/gru/';
var ender = prd_ender; // ⚠️ Ambiente ativo

// Tokens de acesso (⚠️ hardcoded!)
var tokenAcesso = "xxxxx";
var tokenAcessoCCCPM = "xxxxx";
var tokenAcessoCCCPM2 = "xxxxx";
var tokenAcessoPAPEM = "xxxxx";

// SSL Certificates (server.js:69-72)
var options = {
  key: fs.readFileSync('/var/www/html/pagtesouro/certificados/pagtesouro.key'),
  cert: fs.readFileSync('/var/www/html/pagtesouro/certificados/pagtesouro.pem')
};

// Database (inline em cada rota)
const pool = new Pool({
  user: '#',
  host: '#',
  database: '#',
  schema: '#',
  password: '#',  // ⚠️ hardcoded!
  port: 5432
})
```

**Problemas**:
- ❌ Credenciais hardcoded no código-fonte
- ❌ Não usa variáveis de ambiente
- ❌ Difícil trocar entre ambientes (HMG/PRD)
- ❌ Risco de segurança (senhas no git)

**Onde deveria estar**:
```javascript
// src/config/environment.js
require('dotenv').config()

module.exports = {
  env: process.env.NODE_ENV || 'development',
  port: process.env.PORT || 3000,

  pagtesouro: {
    baseUrl: process.env.PAGTESOURO_URL,
    tokens: {
      default: process.env.PAGTESOURO_TOKEN,
      cccpm: process.env.PAGTESOURO_TOKEN_CCCPM,
      cccpm2: process.env.PAGTESOURO_TOKEN_CCCPM2,
      papem: process.env.PAGTESOURO_TOKEN_PAPEM
    }
  },

  database: {
    host: process.env.DB_HOST,
    port: process.env.DB_PORT,
    database: process.env.DB_NAME,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    schema: 'pagtesouro'
  },

  ssl: {
    key: process.env.SSL_KEY_PATH,
    cert: process.env.SSL_CERT_PATH
  }
}

// .env
NODE_ENV=production
PORT=3000
PAGTESOURO_URL=https://pagtesouro.tesouro.gov.br/api/gru/
PAGTESOURO_TOKEN=xxxxx
DB_HOST=localhost
DB_PASSWORD=secret
```

---

### ⚠️ Error Handling (Errors)

**Localização Atual**: Espalhado por server.js

**Try-Catch Blocks**:
- server.js:280-307 - Erros da API PagTesouro
- server.js:184-194 - Erros de banco de dados
- server.js:394-407 - Erros da API SINGRA
- server.js:486-500 - Erros de atualização

**Exemplos**:
```javascript
// server.js:280-302
catch (error) {
  var erro = new Object();
  if (typeof(error.response) !== 'undefined') {
    console.log(error.response.data);
    for (const [key,value] of Object.entries(error.response.data)) {
      geralog("Erro: " + value["codigo"] + " - " + value["descricao"]);
      erro[value["codigo"]] = value["descricao"];
    }
    erro["situacao"] = { codigo: 'CORRIGIR' };
    response.send(erro);
  } else {
    erro["situacao"] = { codigo: 'ERRO' };
    response.send(erro);
  }
}

// server.js:184-194 (Database)
pool.query(query, values, (err, res) => {
  if (err) throw (new Date().toLocaleString() + " Erro no registro!");
  // ...
});
```

**Problemas**:
- ❌ Tratamento inconsistente de erros
- ❌ Alguns erros são apenas logged, outros lançam exceptions
- ❌ Não há middleware centralizado de erros
- ❌ Mensagens de erro expostas ao cliente
- ❌ Stack traces podem vazar informações

**Onde deveria estar**:
```javascript
// src/middleware/errorHandler.js
class AppError extends Error {
  constructor(message, statusCode) {
    super(message)
    this.statusCode = statusCode
    this.isOperational = true
  }
}

const errorHandler = (err, req, res, next) => {
  err.statusCode = err.statusCode || 500
  err.status = err.status || 'error'

  if (process.env.NODE_ENV === 'development') {
    res.status(err.statusCode).json({
      status: err.status,
      error: err,
      message: err.message,
      stack: err.stack
    })
  } else {
    // Produção: não expor detalhes internos
    if (err.isOperational) {
      res.status(err.statusCode).json({
        status: err.status,
        message: err.message
      })
    } else {
      logger.error('Erro não tratado:', err)
      res.status(500).json({
        status: 'error',
        message: 'Erro interno do servidor'
      })
    }
  }
}

module.exports = { AppError, errorHandler }

// Uso:
throw new AppError('Pagamento não encontrado', 404)
```

---

### 🗄️ Repository (Data Access Layer)

**Localização Atual**: ❌ **NÃO EXISTE COMO CAMADA**

Queries SQL estão **inline** dentro das rotas:

**Queries Identificadas**:

1. **SELECT sequencial** (server.js:123-131):
```javascript
var query = `SELECT COALESCE (MAX(CD_REF_SEQ), 0) AS seq
             FROM pagtesouro.tb_pgto
             WHERE cd_cpf = $1 AND cd_om = $2`;
var values = [value.cpf, value.om];
pool.query(query, values, (err, res) => { ... });
```

2. **INSERT pagamento** (server.js:166-183):
```javascript
var query = `INSERT INTO pagtesouro.tb_pgto
             (id_pgto, id_servico, dt_criacao, ds_situacao, ...)
             VALUES ($1, $2, $3, $4, ...)`;
var values = [idPgto, idServico, dataCriacao, ...];
pool.query(query, values, (err, res) => { ... });
```

3. **UPDATE status** (server.js:355-373):
```javascript
var query = `UPDATE pagtesouro.tb_pgto
             SET ds_tp_pgto = $1, vr_pago = $2, ...
             WHERE id_pgto = $7`;
var values = [tipoPgto, valorPago, ...];
pool.query(query, values, (err, res) => { ... });
```

4. **UPDATE SINGRA flag** (server.js:465-485):
```javascript
var query = `UPDATE pagtesouro.tb_pgto
             SET singra_ok = 1
             WHERE id_pgto = $1`;
var values = [idPgto];
pool.query(query, values, (err, res) => { ... });
```

**Problemas**:
- ❌ Queries SQL espalhadas pelo código
- ❌ Nova conexão de pool criada em cada requisição
- ❌ Difícil de testar (lógica acoplada ao banco)
- ❌ Difícil de trocar banco de dados
- ❌ Sem reutilização de código

**Onde deveria estar**:
```javascript
// src/repositories/paymentRepository.js
const pool = require('../config/database')

class PaymentRepository {
  async getNextSequential(cpf, om) {
    const query = `
      SELECT COALESCE(MAX(CD_REF_SEQ), 0) AS seq
      FROM pagtesouro.tb_pgto
      WHERE cd_cpf = $1 AND cd_om = $2
    `
    const result = await pool.query(query, [cpf, om])
    return result.rows[0].seq
  }

  async create(payment) {
    const query = `INSERT INTO pagtesouro.tb_pgto (...) VALUES (...)`
    const values = [payment.id, payment.servico, ...]
    await pool.query(query, values)
    return payment
  }

  async updateStatus(idPgto, statusData) {
    const query = `UPDATE pagtesouro.tb_pgto SET ... WHERE id_pgto = $1`
    await pool.query(query, [statusData, idPgto])
  }

  async markSingraNotified(idPgto) {
    const query = `UPDATE pagtesouro.tb_pgto SET singra_ok = 1 WHERE id_pgto = $1`
    await pool.query(query, [idPgto])
  }
}

module.exports = new PaymentRepository()
```

---

### 💼 Services (Business Logic)

**Localização Atual**: ❌ **NÃO EXISTE COMO CAMADA**

Lógica de negócio está **inline** nas rotas:

**Lógicas Identificadas**:

1. **Geração de Código de Referência** (server.js:125-157):
```javascript
var montaref = (value, seq) => {
  var ref = "";
  var ano = value.ano;
  var servico = value.servico;
  var cpf = parseInt(value.cpf);
  var om = value.om;
  var sequencial = seq;

  // Cálculo dos dígitos verificadores
  var dv1 = cpf % 10;
  var dv2 = (cpf + parseInt(om) + parseInt(ano) + parseInt(servico)) % 10;

  ref = ano + servico + cpf + om + sequencial + dv1 + dv2;
  return ref;
}
```

2. **Criptografia de Dados Sensíveis** (server.js:237-261):
```javascript
// AES-128-CBC
var cipher = crypto.createCipheriv('aes-128-cbc', key, iv);
var nome_encrypted = cipher.update(value.nomeContribuinte, 'utf8', 'base64');
nome_encrypted += cipher.final('base64');

var cpf_encrypted = cipher.update(value.cnpjCpf, 'utf8', 'base64');
cpf_encrypted += cipher.final('base64');
```

3. **Seleção de Token por Categoria** (server.js:203-206):
```javascript
var token = tokenAcesso;
if (value.cat_servico == "CCCPM") token = tokenAcessoCCCPM;
if (value.cat_servico == "CCCPM2") token = tokenAcessoCCCPM2;
if (value.cat_servico == "PAPEM") token = tokenAcessoPAPEM;
```

4. **Notificação SINGRA Condicional** (server.js:375-500):
```javascript
if (dsSituacao == "CONCLUIDO" && catServico == "CCIM") {
  // Busca dados do pagamento
  // Notifica SINGRA
  // Atualiza flag singra_ok
}
```

**Onde deveria estar**:
```javascript
// src/services/referenceCodeService.js
class ReferenceCodeService {
  generate(paymentData, sequential) {
    const { ano, servico, cpf, om } = paymentData
    const cpfNum = parseInt(cpf)

    const dv1 = cpfNum % 10
    const dv2 = (cpfNum + parseInt(om) + parseInt(ano) + parseInt(servico)) % 10

    return `${ano}${servico}${cpf}${om}${sequential}${dv1}${dv2}`
  }
}

// src/services/encryptionService.js
class EncryptionService {
  encrypt(text) {
    const cipher = crypto.createCipheriv('aes-128-cbc', this.key, this.iv)
    let encrypted = cipher.update(text, 'utf8', 'base64')
    encrypted += cipher.final('base64')
    return encrypted
  }
}

// src/services/paymentService.js
class PaymentService {
  constructor(paymentRepo, pagTesourosClient, singraClient) {
    this.paymentRepo = paymentRepo
    this.pagTesouros = pagTesourosClient
    this.singra = singraClient
  }

  async createPayment(data) {
    // Orquestra todo o fluxo:
    // 1. Busca sequencial
    // 2. Gera referência
    // 3. Criptografa
    // 4. Chama PagTesouro
    // 5. Salva no banco
  }

  async updatePaymentStatus(idPgto) {
    // Orquestra atualização:
    // 1. Consulta PagTesouro
    // 2. Atualiza banco
    // 3. Se CCIM + CONCLUIDO, notifica SINGRA
  }
}
```

---

### 🛡️ Middleware

**Localização Atual**: server.js:17-61

**Middlewares Configurados**:

1. **HSTS** (HTTP Strict Transport Security):
```javascript
// server.js:17-24
app.use(function(req, res, next) {
  if (req.secure) {
    res.setHeader('Strict-Transport-Security',
      'max-age=31536000; includeSubDomains; preload');
  }
  next();
})
```

2. **Cache Control**:
```javascript
// server.js:25-29
app.use((req, res, next) => {
  res.setHeader('Cache-Control', 'no-cache, no-store');
  res.setHeader('Pragma', 'no-cache');
  next();
})
```

3. **X-Frame-Options**:
```javascript
// server.js:30-34
app.use((req, res, next) => {
  res.setHeader('X-Frame-Options', 'SAMEORIGIN');
  next();
})
```

4. **CORS**:
```javascript
// server.js:35-47
app.use((req, res, next) => {
  res.setHeader('Access-Control-Allow-Origin', '127.0.0.1');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  res.setHeader('Access-Control-Allow-Credentials', 'true');
  next();
})
```

5. **Body Parser**:
```javascript
// server.js:48-50
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());
app.use(cors());
```

**Middlewares Faltando**:
- ❌ **Authentication** - Nenhuma validação de usuário
- ❌ **Authorization** - Nenhum controle de permissões
- ❌ **Rate Limiting** - Sem proteção contra abuso
- ❌ **Request Validation** - Validação de payload inconsistente
- ❌ **Error Handler** - Sem middleware global de erros
- ❌ **Request Logger** - Log de requests não estruturado
- ❌ **Compression** - Sem compressão de respostas
- ❌ **Helmet** - Falta outros headers de segurança

**Onde deveria estar**:
```javascript
// src/middleware/index.js
const security = require('./security')
const auth = require('./auth')
const errorHandler = require('./errorHandler')
const requestLogger = require('./requestLogger')
const rateLimit = require('express-rate-limit')

module.exports = (app) => {
  // Security headers
  app.use(security.hsts)
  app.use(security.xframe)
  app.use(security.cacheControl)

  // Request logging
  app.use(requestLogger)

  // Body parsing
  app.use(express.json())
  app.use(express.urlencoded({ extended: false }))

  // CORS
  app.use(cors({
    origin: process.env.ALLOWED_ORIGINS.split(','),
    credentials: true
  }))

  // Rate limiting
  app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutos
    max: 100 // limite por IP
  }))

  // Authentication (aplicado em rotas específicas)
  // app.use('/api', auth.authenticateJWT)

  // Error handler (deve ser o último)
  app.use(errorHandler)
}
```

---

## 🏗️ Padrões Arquiteturais

### Padrões Atualmente Implementados

#### ✅ 1. Middleware Pattern
**Onde**: server.js:17-61
**Como**: Express middleware para headers de segurança
```javascript
app.use((req, res, next) => {
  // Processa request
  next() // Passa para próximo middleware
})
```
**Avaliação**: Bem implementado, mas incompleto

---

#### ✅ 2. Callback Pattern / Promise Pattern
**Onde**: server.js (mix de callbacks e async/await)
```javascript
// Callback style (PostgreSQL)
pool.query(query, values, (err, res) => {
  if (err) throw err
  // processar resultado
})

// Promise/Async-Await style (Axios)
try {
  const response = await axios.post(url, data)
} catch (error) {
  // handle error
}
```
**Avaliação**: Inconsistente - mistura dois padrões

---

#### ⚠️ 3. Façade Pattern (parcial)
**Onde**: server.js inteiro age como façade
**Como**: Esconde complexidade de PagTesouro, SINGRA, PostgreSQL
**Problema**: Tudo em um arquivo - não é uma verdadeira façade modular

---

### Padrões NÃO Implementados (mas DEVERIAM estar)

#### ❌ 1. MVC (Model-View-Controller)
**Por que usar**: Separação de responsabilidades

```
Controller (Handle HTTP)
    ↓
Service (Business Logic)
    ↓
Repository (Data Access)
```

**Impacto da ausência**:
- Código difícil de testar
- Lógica duplicada
- Difícil de manter

---

#### ❌ 2. Repository Pattern
**O que é**: Abstração de acesso a dados

**Como deveria ser**:
```javascript
// Em vez de:
pool.query('SELECT * FROM tb_pgto WHERE id = $1', [id])

// Usar:
paymentRepository.findById(id)
```

**Benefícios**:
- Facilita testes (mock do repository)
- Centraliza queries
- Facilita troca de banco de dados

---

#### ❌ 3. Dependency Injection (DI)
**O que é**: Inversão de controle - dependências são injetadas

**Como deveria ser**:
```javascript
// Em vez de hardcoded:
const pool = new Pool({ host: 'localhost', ... })

// Usar injeção:
class PaymentService {
  constructor(paymentRepository, pagTesourosClient) {
    this.paymentRepo = paymentRepository
    this.pagTesouros = pagTesourosClient
  }
}

// Instanciar com dependências:
const service = new PaymentService(
  paymentRepository,
  pagTesourosClient
)
```

**Benefícios**:
- Facilita testes (injetar mocks)
- Baixo acoplamento
- Flexibilidade

---

#### ❌ 4. Factory Pattern
**Uso**: Criar instâncias de clientes HTTP, conexões, etc.

```javascript
// src/factories/httpClientFactory.js
class HttpClientFactory {
  static createPagTesourosClient() {
    return axios.create({
      baseURL: config.pagtesouro.baseUrl,
      headers: { 'Authorization': `Bearer ${config.pagtesouro.token}` },
      proxy: config.proxy
    })
  }
}
```

---

#### ❌ 5. Strategy Pattern
**Uso**: Diferentes estratégias de token por serviço

```javascript
// Em vez de:
var token = tokenAcesso;
if (value.cat_servico == "CCCPM") token = tokenAcessoCCCPM;
if (value.cat_servico == "CCCPM2") token = tokenAcessoCCCPM2;

// Usar:
class TokenStrategy {
  getToken(categoria) {
    const strategies = {
      'CCCPM': config.tokens.cccpm,
      'CCCPM2': config.tokens.cccpm2,
      'PAPEM': config.tokens.papem,
      'default': config.tokens.default
    }
    return strategies[categoria] || strategies.default
  }
}
```

---

#### ❌ 6. Adapter Pattern
**Uso**: Adaptar APIs externas para interface interna

```javascript
// src/integrations/pagTesourosAdapter.js
class PagTesourosAdapter {
  constructor(httpClient) {
    this.client = httpClient
  }

  async createPayment(internalPaymentData) {
    // Converte formato interno para formato PagTesouro
    const externalFormat = this.mapToExternalFormat(internalPaymentData)

    const response = await this.client.post('/solicitacao-pagamento', externalFormat)

    // Converte resposta externa para formato interno
    return this.mapToInternalFormat(response.data)
  }

  private mapToExternalFormat(data) { /* ... */ }
  private mapToInternalFormat(data) { /* ... */ }
}
```

**Benefício**: Se PagTesouro mudar API, só precisa mudar o adapter

---

#### ❌ 7. Observer Pattern / Event Emitter
**Uso**: Notificações assíncronas (ex: pagamento concluído → notifica SINGRA)

```javascript
// src/events/paymentEvents.js
const EventEmitter = require('events')
const paymentEmitter = new EventEmitter()

// Listener
paymentEmitter.on('payment.completed', async (payment) => {
  if (payment.categoria === 'CCIM') {
    await singraClient.notifyPayment(payment)
  }
})

// Emit
paymentEmitter.emit('payment.completed', payment)
```

---

#### ❌ 8. Singleton Pattern
**Uso**: Conexão de banco de dados (pool único)

```javascript
// src/config/database.js
const { Pool } = require('pg')

let poolInstance = null

class Database {
  static getInstance() {
    if (!poolInstance) {
      poolInstance = new Pool({
        host: config.database.host,
        // ...
      })
    }
    return poolInstance
  }
}

module.exports = Database.getInstance()
```

**Problema Atual**: Nova pool criada em cada requisição

---

#### ❌ 9. Builder Pattern
**Uso**: Construir payloads complexos para APIs

```javascript
// src/builders/pagTesourosPayloadBuilder.js
class PagTesourosPayloadBuilder {
  constructor() {
    this.payload = {}
  }

  withService(servico) {
    this.payload.codigoServico = servico
    return this
  }

  withContribuinte(nome, cpf) {
    this.payload.nomeContribuinte = nome
    this.payload.cnpjCpf = cpf
    return this
  }

  withValores(principal, juros, multa) {
    this.payload.valorPrincipal = principal
    this.payload.valorJuros = juros
    this.payload.valorMulta = multa
    return this
  }

  build() {
    return this.payload
  }
}

// Uso:
const payload = new PagTesourosPayloadBuilder()
  .withService('12345')
  .withContribuinte('João Silva', '12345678900')
  .withValores(100, 0, 0)
  .build()
```

---

#### ❌ 10. DTO (Data Transfer Object) Pattern
**Uso**: Objetos para transferir dados entre camadas

```javascript
// src/dtos/createPaymentDTO.js
class CreatePaymentDTO {
  constructor(data) {
    this.cpf = data.cpf
    this.nome = data.nome
    this.valorPrincipal = data.valor
    this.codigoServico = data.servico
    // validação aqui
  }

  validate() {
    if (!this.cpf || this.cpf.length !== 11) {
      throw new Error('CPF inválido')
    }
    // mais validações
  }
}
```

---

## 🔗 Pontos de Acoplamento

### 1. ⚠️ Acoplamento Alto: Rotas → Lógica de Negócio → Banco de Dados

**Problema**: Tudo está junto em uma função

```javascript
app.post('/handle', async (request, response) => {
  // 1. Validação (deveria estar em validator)
  if (!request.body.cpf) { ... }

  // 2. Acesso a dados (deveria estar em repository)
  const pool = new Pool({ ... })
  pool.query("SELECT ...", ...)

  // 3. Lógica de negócio (deveria estar em service)
  var codigoReferencia = montaref(value, seq)

  // 4. Criptografia (deveria estar em service)
  var cipher = crypto.createCipheriv(...)

  // 5. API externa (deveria estar em integration)
  await axios.post(url, dados)

  // 6. Mais acesso a dados (deveria estar em repository)
  pool.query("INSERT ...", ...)
})
```

**Impacto**:
- ❌ Impossível testar lógica isoladamente
- ❌ Mudança em um requisito exige alterar função gigante
- ❌ Código duplicado entre rotas

**Solução**: Separar em camadas (Controller → Service → Repository)

---

### 2. ⚠️ Acoplamento Alto: Configuração Hardcoded

**Problema**: Tokens, URLs, credenciais no código

```javascript
var tokenAcesso = "xxxxx";  // ⚠️ Hardcoded
var prd_ender = 'https://pagtesouro.tesouro.gov.br/api/gru/';  // ⚠️ Hardcoded

const pool = new Pool({
  user: '#',  // ⚠️ Hardcoded
  password: '#',  // ⚠️ Hardcoded
  host: '#'  // ⚠️ Hardcoded
})
```

**Impacto**:
- ❌ Trocar ambiente (HMG → PRD) exige alterar código
- ❌ Senhas no repositório Git (risco de segurança)
- ❌ Difícil deploy em diferentes ambientes

**Solução**: Usar variáveis de ambiente (.env)

---

### 3. ⚠️ Acoplamento Alto: Lógica PagTesouro Embutida

**Problema**: Montagem de payload e parsing de resposta misturados com lógica de negócio

```javascript
// server.js:225-266
var retorno = new Object();
retorno["numeroControle"] = value.numeroControle;
retorno["idServico"] = value.idServico;
retorno["codigoServico"] = value.codigoServico;
// ... 40 linhas de montagem de payload
```

**Impacto**:
- ❌ Se API PagTesouro mudar, precisa alterar função inteira
- ❌ Não reutilizável em outros contextos

**Solução**: Criar adapter/integration layer

---

### 4. ⚠️ Acoplamento Alto: Criação de Pool em Cada Requisição

**Problema**: Nova instância de pool criada inline

```javascript
app.post('/handle', async (request, response) => {
  const pool = new Pool({  // ⚠️ Nova pool em CADA request!
    user: '#',
    host: '#',
    database: '#'
  })
  pool.query(...)
})
```

**Impacto**:
- ❌ Performance ruim (overhead de conexão)
- ❌ Possível vazamento de conexões
- ❌ Limite de conexões pode ser atingido

**Solução**: Singleton pool compartilhado

---

### 5. ⚠️ Acoplamento Moderado: Integração SINGRA Acoplada à Atualização

**Problema**: Lógica de notificação SINGRA está dentro da rota `/update`

```javascript
app.post('/update', async (request, response) => {
  // ... atualiza pagamento

  // ⚠️ Lógica específica de SINGRA embutida
  if (dsSituacao == "CONCLUIDO" && catServico == "CCIM") {
    // ... 120 linhas de código SINGRA
    await axios.post('https://api-singra.dabm.mb/...', ...)
  }
})
```

**Impacto**:
- ❌ Rota de update tem responsabilidade dupla
- ❌ Se SINGRA mudar, precisa alterar lógica de update
- ❌ Difícil de testar atualização sem SINGRA

**Solução**: Event-driven (emitir evento payment.completed, listener chama SINGRA)

---

### 6. ⚠️ Acoplamento Alto: Criptografia Inline

**Problema**: Algoritmo AES hardcoded, chave e IV inline

```javascript
var key = CryptoJS.enc.Utf8.parse('AAAAAAAAAAAAAAAA');  // ⚠️ Chave fraca!
var iv = CryptoJS.enc.Utf8.parse('AAAAAAAAAAAAAAAA');   // ⚠️ IV fixo!
var cipher = crypto.createCipheriv('aes-128-cbc', key, iv);
```

**Impacto**:
- ❌ Chave e IV fixos (inseguro)
- ❌ Não reutilizável
- ❌ Difícil trocar algoritmo

**Solução**: EncryptionService com configuração externa

---

### 7. ⚠️ Acoplamento Alto: Seleção de Token com IFs

**Problema**: Lógica de seleção hardcoded

```javascript
var token = tokenAcesso;
if (value.cat_servico == "CCCPM") token = tokenAcessoCCCPM;
if (value.cat_servico == "CCCPM2") token = tokenAcessoCCCPM2;
if (value.cat_servico == "PAPEM") token = tokenAcessoPAPEM;
```

**Impacto**:
- ❌ Adicionar novo serviço exige alterar código
- ❌ Lógica duplicada (aparece múltiplas vezes)

**Solução**: Strategy pattern ou mapa de configuração

---

### 8. ⚠️ Acoplamento Alto: Queries SQL Inline

**Problema**: SQL espalhado pelo código

```javascript
var query = `SELECT COALESCE (MAX(CD_REF_SEQ), 0) AS seq
             FROM pagtesouro.tb_pgto
             WHERE cd_cpf = $1 AND cd_om = $2`;
pool.query(query, values, (err, res) => { ... });
```

**Impacto**:
- ❌ Mudança no schema exige buscar queries por todo código
- ❌ Difícil de testar lógica sem banco real
- ❌ Sem reutilização

**Solução**: Repository pattern

---

## 🔧 Recomendações de Refatoração

### Prioridade 1: Crítica (Segurança e Estabilidade)

#### 1.1. 🔐 Mover Credenciais para Variáveis de Ambiente

**Por quê**: Senhas e tokens hardcoded são risco de segurança

**Como**:
```bash
# Criar .env
npm install dotenv

# .env
PAGTESOURO_TOKEN=xxxxx
DB_PASSWORD=xxxxx
SINGRA_PASSWORD=xxxxx

# server.js
require('dotenv').config()
const token = process.env.PAGTESOURO_TOKEN
```

**Esforço**: 2 horas
**Benefício**: Elimina risco de credenciais expostas

---

#### 1.2. 🔒 Adicionar Autenticação nos Endpoints

**Por quê**: Atualmente qualquer um pode criar/atualizar pagamentos

**Como**:
```javascript
const jwt = require('jsonwebtoken')

const authenticateJWT = (req, res, next) => {
  const token = req.headers.authorization?.split(' ')[1]
  if (!token) return res.status(401).json({ error: 'Token não fornecido' })

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) return res.status(403).json({ error: 'Token inválido' })
    req.user = user
    next()
  })
}

app.post('/handle', authenticateJWT, async (req, res) => { ... })
```

**Esforço**: 4 horas
**Benefício**: Protege endpoints de acesso não autorizado

---

#### 1.3. 💾 Singleton Database Pool

**Por quê**: Pool criado em cada request causa vazamento de conexões

**Como**:
```javascript
// config/database.js
const { Pool } = require('pg')
const pool = new Pool({
  host: process.env.DB_HOST,
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  max: 20, // máximo de conexões
  idleTimeoutMillis: 30000
})

module.exports = pool

// Uso:
const pool = require('./config/database')
pool.query(...)
```

**Esforço**: 1 hora
**Benefício**: Melhora performance e estabilidade

---

### Prioridade 2: Alta (Manutenibilidade)

#### 2.1. 📂 Separar Código em Camadas (MVC)

**Estrutura Alvo**:
```
src/
├── routes/
│   └── payment.routes.js
├── controllers/
│   └── paymentController.js
├── services/
│   ├── paymentService.js
│   ├── referenceCodeService.js
│   └── encryptionService.js
├── repositories/
│   └── paymentRepository.js
└── integrations/
    ├── pagTesourosClient.js
    └── singraClient.js
```

**Esforço**: 16 horas (2 dias)
**Benefício**: Código testável, manutenível, escalável

---

#### 2.2. 🧪 Adicionar Testes

**Tipos**:
- **Unit tests**: Services, repositories
- **Integration tests**: Endpoints completos
- **E2E tests**: Fluxos completos

**Ferramentas**:
```bash
npm install --save-dev jest supertest
```

**Exemplo**:
```javascript
// tests/unit/referenceCodeService.test.js
describe('ReferenceCodeService', () => {
  it('deve gerar código de referência válido', () => {
    const code = referenceCodeService.generate({
      ano: '2025',
      servico: '1234',
      cpf: '12345678900',
      om: '5678'
    }, 1)

    expect(code).toMatch(/^\d{19}$/)
  })
})
```

**Esforço**: 24 horas (3 dias)
**Benefício**: Confiança em mudanças, previne regressões

---

#### 2.3. 📝 Implementar Logging Estruturado

**Ferramenta**: Winston ou Pino

```bash
npm install winston
```

```javascript
// config/logger.js
const winston = require('winston')

const logger = winston.createLogger({
  level: 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.json()
  ),
  transports: [
    new winston.transports.File({ filename: 'error.log', level: 'error' }),
    new winston.transports.File({ filename: 'combined.log' })
  ]
})

// Uso:
logger.info('Pagamento criado', { idPgto, valor, cpf: '***' })
logger.error('Erro ao chamar PagTesouro', { error: err.message })
```

**Esforço**: 4 horas
**Benefício**: Debugging facilitado, monitoramento eficiente

---

### Prioridade 3: Média (Boas Práticas)

#### 3.1. ✅ Adicionar Validação de Entrada

**Ferramenta**: Joi ou express-validator

```bash
npm install joi
```

```javascript
// validators/paymentValidator.js
const Joi = require('joi')

const createPaymentSchema = Joi.object({
  cpf: Joi.string().length(11).required(),
  nome: Joi.string().min(3).max(100).required(),
  valorPrincipal: Joi.number().positive().required(),
  codigoServico: Joi.string().required()
})

const validate = (schema) => (req, res, next) => {
  const { error } = schema.validate(req.body)
  if (error) {
    return res.status(400).json({ error: error.details[0].message })
  }
  next()
}

// Uso:
app.post('/handle', validate(createPaymentSchema), paymentController.create)
```

**Esforço**: 4 horas
**Benefício**: Previne dados inválidos, melhora experiência do usuário

---

#### 3.2. 🚦 Adicionar Rate Limiting

**Ferramenta**: express-rate-limit

```bash
npm install express-rate-limit
```

```javascript
const rateLimit = require('express-rate-limit')

const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutos
  max: 100, // limite por IP
  message: 'Muitas requisições deste IP, tente novamente mais tarde'
})

app.use('/handle', limiter)
app.use('/update', limiter)
```

**Esforço**: 1 hora
**Benefício**: Protege contra abuso e DDoS

---

#### 3.3. 📖 Documentar API com OpenAPI/Swagger

**Ferramenta**: swagger-jsdoc + swagger-ui-express

```bash
npm install swagger-jsdoc swagger-ui-express
```

```javascript
/**
 * @swagger
 * /handle:
 *   post:
 *     summary: Cria solicitação de pagamento
 *     tags: [Pagamentos]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               cpf:
 *                 type: string
 *               nome:
 *                 type: string
 *     responses:
 *       200:
 *         description: Pagamento criado com sucesso
 */
```

**Esforço**: 6 horas
**Benefício**: Documentação sempre atualizada, facilita integração

---

### Prioridade 4: Baixa (Melhorias Incrementais)

#### 4.1. 🗜️ Adicionar Compressão de Respostas

```bash
npm install compression
```

```javascript
const compression = require('compression')
app.use(compression())
```

**Esforço**: 15 minutos
**Benefício**: Reduz bandwidth, melhora performance

---

#### 4.2. 🔍 Adicionar Healthcheck Endpoint

```javascript
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    uptime: process.uptime(),
    timestamp: new Date().toISOString()
  })
})

app.get('/health/db', async (req, res) => {
  try {
    await pool.query('SELECT 1')
    res.json({ status: 'ok', database: 'connected' })
  } catch (error) {
    res.status(503).json({ status: 'error', database: 'disconnected' })
  }
})
```

**Esforço**: 30 minutos
**Benefício**: Monitoramento facilitado

---

## 📊 Resumo: Estado Atual vs. Ideal

| Aspecto | Estado Atual | Estado Ideal | Prioridade |
|---------|-------------|--------------|------------|
| **Arquitetura** | Monolito procedural | Layered (MVC) | Alta |
| **Configuração** | Hardcoded | Variáveis de ambiente | Crítica |
| **Autenticação** | ❌ Nenhuma | JWT/Session | Crítica |
| **Autorização** | ❌ Nenhuma | Role-based | Alta |
| **Validação** | ⚠️ Parcial | Joi/express-validator | Média |
| **Error Handling** | ⚠️ Inconsistente | Middleware global | Alta |
| **Logging** | console.log | Winston/Pino | Alta |
| **Database** | Pool inline | Singleton pool | Crítica |
| **Testes** | ❌ Nenhum | Unit + Integration | Alta |
| **Documentação** | ⚠️ Parcial | OpenAPI/Swagger | Média |
| **Rate Limiting** | ❌ Nenhum | express-rate-limit | Média |
| **Monitoramento** | ❌ Nenhum | Healthcheck endpoints | Baixa |

---

## 🎓 Guia para Novos Desenvolvedores

### Primeiro Dia: Compreenda o Fluxo

1. **Leia este documento inteiro** 📖
2. **Execute o sistema localmente**:
   ```bash
   git clone <repo>
   cd dgom_pagtesouro
   npm install
   node server.js
   ```
3. **Faça um teste manual com papem_test.js**:
   ```bash
   node papem_test.js
   ```
4. **Leia server.js linha por linha** (588 linhas)

### Primeira Semana: Entenda as Integrações

1. **PagTesouro API**:
   - Documentação: [PagTesouro Docs]
   - Testes em homologação: `https://valpagtesouro.tesouro.gov.br`

2. **SINGRA API**:
   - Sistema interno de saldos
   - Apenas notifica quando categoria = "CCIM"

3. **PostgreSQL**:
   - Leia `banco-dgom.md` para entender schema
   - Conecte no banco e explore tabela `tb_pgto`

### Primeiro Mês: Contribua

1. **Tarefas Iniciantes**:
   - Migrar credenciais para .env
   - Adicionar healthcheck endpoint
   - Adicionar validação de CPF
   - Melhorar logs (adicionar contexto)

2. **Tarefas Intermediárias**:
   - Extrair função montaref para service
   - Criar repository para queries SQL
   - Adicionar testes unitários

3. **Tarefas Avançadas**:
   - Refatorar para arquitetura em camadas
   - Implementar autenticação JWT
   - Adicionar documentação OpenAPI

---

## 📞 Pontos de Contato para Dúvidas

| Área | Responsável | Contato |
|------|-------------|---------|
| PagTesouro API | Tesouro Nacional | [suporte@tesouro.gov.br] |
| SINGRA API | Time DABM | [suporte.singra@mb.gov.br] |
| Infraestrutura | Time DevOps | [devops@dgom.mb] |
| Banco de Dados | DBA Team | [dba@dgom.mb] |

---

## 🔚 Conclusão

Este sistema é uma **aplicação monolítica simples** que funciona, mas carece de boas práticas modernas de engenharia de software. O código está **altamente acoplado**, com **zero separação de responsabilidades**, e **sem proteção de autenticação**.

### Próximos Passos Recomendados:

1. **Imediato** (1 semana):
   - Migrar credenciais para .env
   - Criar singleton database pool
   - Adicionar autenticação básica

2. **Curto Prazo** (1 mês):
   - Refatorar para MVC
   - Adicionar testes
   - Implementar logging estruturado

3. **Médio Prazo** (3 meses):
   - Documentação OpenAPI
   - Monitoramento e observabilidade
   - CI/CD pipeline

---

**Documento criado em**: 2025-12-03
**Versão**: 1.0
**Autor**: Análise Automatizada - Claude Code
**Última Atualização**: 2025-12-03
