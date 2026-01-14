# DGOM PagTesouro Proxy

Servidor Node.js responsável por intermediar requisições entre consumidores internos e a API do PagTesouro, garantindo aplicação de regras de negócio específicas da DGOM, registro das transações em banco PostgreSQL e comunicação adicional com o sistema SINGRA.

## Requisitos e dependências

- Node.js 16 ou superior
- Certificados TLS locais (`pagtesouro.key`, `pagtesouro.pem` e `recim-chain.pem`)
- Acesso a banco PostgreSQL
- Dependências npm:
  - express
  - body-parser
  - axios-https-proxy-fix
  - ssl-root-cas
  - cors
  - pg

Instale-as via `npm install express body-parser axios-https-proxy-fix ssl-root-cas cors pg`.

## Instalação

1. Certifique-se de ter o Node.js instalado.
2. Coloque as chaves/certificados TLS no diretório raiz do projeto (`pagtesouro.key`, `pagtesouro.pem`, `recim-chain.pem`).
3. Atualize os valores sensíveis em `pgt.js` (tokens de acesso, credenciais de proxy e banco).
4. Instale as dependências:
   ```bash
   npm install express body-parser axios-https-proxy-fix ssl-root-cas cors pg
   ```

## Exemplos de uso

### Inicialização

```bash
node pgt.js
```

A API HTTPS ficará disponível em `https://localhost:3000`.

### Criar GRU

```bash
curl -k -X POST https://localhost:3000/handle \
  -H "Content-Type: application/json" \
  -d '{
    "cnpjCpf": "12345678901",
    "cat": "CCIM",
    "nomeUG": "78000",
    "nomeContribuinte": "Fulano de Tal",
    "valorPrincipal": 100
  }'
```

### Atualizar status

```bash
curl -k -X POST https://localhost:3000/update \
  -H "Content-Type: application/json" \
  -d '{
    "id_pgto": "ID_DO_PAGAMENTO",
    "cat_servico": "CCIM",
    "cd_cpf": "12345678901"
  }'
```

## Estrutura de pastas

```
.
├── pgt.js          # Servidor Express com integrações PagTesouro/SINGRA e Postgres
└── README.md       # Documentação do projeto
```

## Variáveis de ambiente

Atualmente as credenciais estão definidas diretamente no código. Para produção, recomenda-se exportar as seguintes variáveis e ajustar `pgt.js` para consumi-las:

- `PAGTESOURO_TOKEN` – Token principal do PagTesouro
- `PAGTESOURO_TOKEN_CCCPM` – Token específico para a categoria CCCPM
- `PAGTESOURO_TOKEN_CCCPM2` – Token específico para a categoria CCCPM2
- `PAGTESOURO_TOKEN_PAPEM` – Token específico para PAPEM
- `PROXY_AUTH` – Credencial de autenticação do proxy em formato Base64
- `PGUSER`, `PGHOST`, `PGDATABASE`, `PGPASSWORD`, `PGPORT` – Parâmetros de conexão com PostgreSQL

Sempre evite manter segredos versionados e utilize um gerenciador seguro para injetá-los em tempo de execução.
