# Documentação Técnica – Integração PagTesouro (DGOM)

Aqui eu junto a visão técnica e arquitetural do sistema que tá na RECIM.

## 1. Visão Geral

- **Pra que serve:** rodar os serviços auxiliares da integração com o PagTesouro (criar/atualizar pagamentos GRU) e entregar uma interface administrativa de consulta/exportação. Em alguns cenários, também manda pagamento concluído pro SINGRA.  
- **Pra quem é:**  
  - **Dev:** manutenção das APIs Node/Express e PHP, ajustes de integração e infraestrutura.  
  - **Operadores/usuários finais:** militares/gestores que geram solicitações de pagamento no formulário web.  
  - **Admin/suporte:** equipes que acompanham pagamentos na grade “grid/” e rodam scripts de teste.  
- **O que faz:**  
  - Cria GRU no PagTesouro via `/handle`.  
  - Atualiza status via `/update`, consolida no PostgreSQL e notifica o SINGRA na categoria CCIM.  
  - Criptografa dados sensíveis (nome, CPF/CNPJ) antes de gravar.  
  - Interface web pra operar com grid filtrável e exportação XLSX.  
  - Script CLI (`papem_test.js`) pra testar geração de GRU PAPEM.

## 2. Arquitetura

### Componentes e interações (alto nível)

- **Frontend de solicitação** (`INDEX/`): formulário PHP/JS que pega os dados do pagamento e chama o backend Node (porta 3000 por HTTPS).  
- **API Node/Express** (`server.js`): expõe `/handle` (criar GRU) e `/update` (atualizar status); fala com PagTesouro (HTTPs + proxy), grava/atualiza PostgreSQL e, no CCIM, notifica o SINGRA via HTTPS.  
- **Persistência**: PostgreSQL (`pagtesouro.tb_pgto` e `pagtesouro.tb_servico`).  
- **Interface administrativa** (`grid/`): PHP que consulta PostgreSQL, descriptografa campos e renderiza o grid (jqxGrid) com exportação XLSX e botões de atualização/comprovante.  
- **Scripts utilitários**: `papem_test.js` manda requisições de teste PAPEM direto pro PagTesouro.

Interação resumida (bem direto):

```
Formulário PHP -> API Node (/handle) -> PagTesouro -> PostgreSQL
                                          |
                                          └-> (/update) -> PagTesouro -> PostgreSQL -> (CCIM) SINGRA
Interface grid PHP -> PostgreSQL (descriptografa) -> UI jqxGrid/Export
```

### Padrões e decisões
- **Express + middlewares** pra roteamento e parsing JSON/URL-encoded.  
- **Separação por endpoint**: `/handle` (criação) e `/update` (atualização/propagação).  
- **Criptografia simétrica (AES-128-CBC)** pra nome e CPF/CNPJ antes de gravar.  
- **Segurança HTTP**: HSTS, cache-control no-cache, X-Frame-Options SAMEORIGIN e CORS restritivo.  
- **Uso de proxy HTTPs** nas chamadas externas, configurado direto no axios.  
- **Grid virtualizada** com jqxGrid (filtro, paginação, exportação) consumindo JSON do PHP.

### Stack tecnológica
- **Backend API:** Node.js, Express, body-parser, axios-https-proxy-fix, ssl-root-cas, https/fs, cors, pg, crypto.  
- **Frontend/formulário:** PHP 7+, jQuery 3.5.1, axios, jQuery UI, máscaras, toastify, MonthPicker.  
- **Grid administrativa:** PHP 7+, jqWidgets (jqxGrid), XLSX, Bootstrap.  
- **Banco:** PostgreSQL 11+ (credenciais/host definidos nos arquivos, é melhor usar variáveis de ambiente).  
- **Infra:** HTTPS com certificados locais (`pagtesouro.key/.pem` ou caminhos absolutos em produção), proxy corporativo (`proxy-1dn.mb:6060`).

### Estrutura de diretórios (principais)
- `/server.js` – API HTTPS integrada ao PagTesouro/SINGRA.  
- `/INDEX/` – Formulário de geração de GRU e autenticação.  
- `/grid/` – Interface administrativa (PHP + JS + jqxGrid) e fonte de dados `data2.php`.  
- `/papem_payload.example.json` – Exemplo de payload PAPEM.  
- `/papem_test.js` – Script de teste CLI para GRU PAPEM.  
- `/DGOM_PAGTESOURO.sql`, `/banco-dgom.md` – Artefatos de banco (dump/notas).

## 3. Instalação e Configuração

### Pré-requisitos
- Node.js 14+ e npm/yarn.  
- PHP 7.4+ com extensões `pgsql` e `openssl`.  
- PostgreSQL acessível com schema `pagtesouro` (tabelas `tb_pgto` e `tb_servico`).  
- Certificados TLS válidos pro host onde a API Node vai rodar.  
- Acesso ao proxy corporativo (`proxy-1dn.mb:6060`) e aos endpoints do PagTesouro/SINGRA.

### Passos de instalação
1. Instalar dependências Node (na raiz):  
   ```bash
   npm install express body-parser axios-https-proxy-fix ssl-root-cas cors pg
   ```
2. Provisionar certificados: copie `pagtesouro.key` e `pagtesouro.pem` (ou configure caminhos absolutos em `server.js`).  
3. Configurar credenciais e tokens (ver seção Variáveis de Ambiente).  
4. Configurar PHP (Apache/Nginx + PHP-FPM) apontando `INDEX/` e `grid/` conforme vhost.  
5. Criar e popular o schema no PostgreSQL usando `DGOM_PAGTESOURO.sql` (se aplicável) e garantir usuário com permissão de leitura/escrita.

### Variáveis de ambiente / segredos recomendados
Troca os valores hardcoded por variáveis de ambiente e carrega via `process.env`:
- `PAGTESOURO_ENDPOINT` (default produção) e `PAGTESOURO_ENDPOINT_HMG`.  
- `PAGTESOURO_TOKEN`, `PAGTESOURO_TOKEN_CCCPM`, `PAGTESOURO_TOKEN_CCCPM2`, `PAGTESOURO_TOKEN_PAPEM`.  
- `PAGTESOURO_PROXY_AUTH` e `HTTPS_PROXY_HOST/PORT` se diferir do padrão.  
- `PGUSER`, `PGHOST`, `PGDATABASE`, `PGPASSWORD`, `PGPORT`, `PGSCHEMA`.  
- `AES_KEY`, `AES_IV` para criptografia dos campos sensíveis.  
- `SINGRA_BASIC_AUTH` (usuário/senha base64) e `SINGRA_CERT_PATH`.  
- Caminhos de `TLS_KEY_PATH` e `TLS_CERT_PATH`.

### Configurações iniciais
- Ajusta `hmg_ender`/`prd_ender` e `aut` (proxy) em `server.js` conforme o ambiente.  
- Garante que o Node escuta na porta 3000 com HTTPS (precisa de certificados).  
- Valida se o proxy aceita chamadas externas (PagTesouro e SINGRA).  
- No PHP, configura `conpg11.php` com as credenciais corretas do PostgreSQL e sessão segura.

## 4. Documentação da API/Código

### API Node (HTTPS)

#### `POST /handle` (`server.js`)
- **Pra que serve:** criar uma nova GRU no PagTesouro e gravar os metadados.  
- **Entrada:** JSON do PagTesouro contendo, entre outros, `cat`, `nomeUG`, `id_servico`, `codigoServico`, `nomeContribuinte`, `cnpjCpf`, `competencia`, `vencimento`, valores, rubricas e campos adicionais PAPEM.  
- **Como rola:**  
  1. Busca sequência `cd_ref_seq` no PostgreSQL por CPF/CNPJ e UG (ou categoria PAPEM).  
  2. Monta `referencia` concatenando UG/categoria + CPF/CNPJ normalizado + sequência.  
  3. Seleciona token conforme `cat_servico`.  
  4. Faz `POST` para `…/solicitacao-pagamento` via axios com proxy.  
  5. Criptografa `nomeContribuinte` e `cnpjCpf` (AES-128-CBC) e insere na tabela `pagtesouro.tb_pgto`.  
- **Saída:** JSON devolvido pelo PagTesouro ou objeto de erro com `situacao.codigo` `CORRIGIR/ERRO`.  
- **Erros comuns:** falta de CPF (`Campo CPF vazio!`), falha de banco, resposta de erro do PagTesouro (códigos em `error.response.data`).【F:server.js†L73-L243】

#### `POST /update` (`server.js`)
- **Pra que serve:** consultar status de um pagamento no PagTesouro e atualizar o registro local; se for CCIM, pode notificar o SINGRA.  
- **Entrada:** JSON com `id_pgto` e `cat_servico` (usa pra decidir token); pode trazer `cd_cpf` pra notificação CCIM.  
- **Como rola:**  
  1. GET `pagamentos/{id_pgto}` no PagTesouro.  
  2. Atualiza `tb_pgto` com situação/valores retornados.  
  3. Se categoria `CCIM` e status `CONCLUIDO`, envia POST para SINGRA (`/pagtesouro/pagamento`) com basic auth e certificado adicional, então marca `singra_ok = 1`.  
- **Saída:**  
  - `"1"` para update simples,  
  - `["1 ok"]` ou `["1 fail", mensagem]` para CCIM (SINGRA),  
  - `"0"` em erro de consulta.  
- **Erros comuns:** falha de proxy/PagTesouro, erro ao atualizar PostgreSQL, erro SINGRA (retorna `erro_Msg`).【F:server.js†L245-L533】

#### Considerações adicionais
- **Criptografia:** chaves/IV definidos no código; é melhor externalizar.  
- **Segurança HTTP:** HSTS, cache-control no-cache, X-Frame-Options SAMEORIGIN e CORS limitado a `127.0.0.1`.  
- **HTTPS:** servidor Express criado com certificados locais definidos em `options`.  
- **Proxy:** axios configurado com host `proxy-1dn.mb` porta `6060`, com cabeçalho `Proxy-Autorization`.

### Interface administrativa (PHP)

- **`grid/data2.php`:** serviço JSON consumido pelo jqxGrid. Valida sessão, lê filtros/ordenação, consulta `tb_pgto`, descriptografa `nome` e `cd_cpf` (AES-128-CBC) e permite atualizar `ds_obs` via POST `update=true`. Diferencia consultas para categorias PAPEM/IMH ou UG específicas.【F:grid/data2.php†L1-L195】  
- **`grid/index.php`:** página da grade. Define colunas/fields por UG, chama `data2.php` via jqxGrid, aciona `/update` da API Node pra atualizar status, exporta XLSX com `xlsx.full.min.js` e oferece botão de comprovante (PDF externo). Tem mensagens de orientação e overlay de loading.【F:grid/index.php†L1-L418】

### Formulário de geração (`INDEX/index.php`)
- Monta seleção de serviços por categoria, coleta dados do contribuinte, aplica máscaras/validação e manda payload pro backend PagTesouro (mesma porta 3000). Usa sessão PHP e carrega lista de serviços de `pagtesouro.tb_servico`.【F:INDEX/index.php†L1-L200】

### Script CLI PAPEM (`papem_test.js`)
- Lê payload JSON (default `papem_payload.example.json`), injeta `Authorization: Bearer ${PAPEM_TOKEN}` e envia POST pra `PAGTESOURO_ENDPOINT` (default VAL). Imprime status/cabeçalhos/resposta; encerra com erro se variáveis ausentes ou payload inválido.【F:papem_test.js†L1-L74】

## 5. Fluxos e Casos de Uso

- **Gerar pagamento (operador):**  
  1. Loga via `INDEX/login.php` e preenche formulário `INDEX/index.php`.  
  2. Frontend manda payload pra API `/handle`.  
  3. API cria GRU no PagTesouro, grava `tb_pgto` e retorna situação `CRIADO`.  
  4. Operador pode emitir a guia/seguir o fluxo no PagTesouro.

- **Acompanhar status (admin/UG):**  
  1. Acessa `grid/index.php`, que carrega dados via `data2.php`.  
  2. Pra registros pendentes, usa botão **Atualizar** → chama `/update` → status/valores persistidos.  
  3. Pra status `CONCLUIDO`, botão **Comprovante** gera PDF (fora do escopo deste material).  
  4. Pode exportar XLSX com todos os campos visíveis.

- **Propagação CCIM pro SINGRA:**  
  - Quando `/update` identifica `cat_servico == "CCIM"` e status `CONCLUIDO`, monta objeto `singra` e envia POST HTTPS com basic auth e certificado adicional; marca `singra_ok = 1` se der certo ou se o pagamento já estiver registrado.

### Sequência simplificada (criação)
```
Formulário PHP -> POST /handle -> PagTesouro solicitacao-pagamento
                   | success
                   v
               INSERT tb_pgto (dados criptografados)
                   |
                   v
             Resposta JSON ao frontend
```

### Sequência simplificada (atualização + SINGRA)
```
Grid -> POST /update -> GET PagTesouro pagamentos/{id}
                             |
                             v
                       UPDATE tb_pgto
                             |
            (se CCIM + CONCLUIDO) -> POST SINGRA -> UPDATE singra_ok
```

## 6. Segurança e Performance

- **Segurança implementada:**  
  - HTTPS obrigatório (certificados locais).  
  - HSTS e anti-cache nas respostas Express.  
  - X-Frame-Options SAMEORIGIN (mitiga clickjacking).  
  - CORS restrito.  
  - Criptografia AES-128-CBC de nome e CPF/CNPJ antes de gravar; descriptografa na leitura.  
  - Sessões PHP obrigatórias na grade e no formulário; redireciona se não autenticado.  
  - Sanitização básica de ordenação em `data2.php` (removendo caracteres perigosos).

- **Considerações/limitações:**  
  - Tokens, chaves e credenciais estão hardcoded em `server.js` no snapshot; vale migrar pra variáveis de ambiente/secret manager.  
  - Caminhos de certificado são absolutos na produção (`/var/www/html/...`); vale parametrizar pra evitar acoplamento.  
  - Falta retry/backoff pra chamadas externas; axios/proxy não tratam timeouts explicitamente.  
  - Logs são só console; vale centralizar e mascarar PII antes de registrar.
