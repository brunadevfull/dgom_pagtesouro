# Documentação Técnica – Integração PagTesouro (DGOM)

## 1. Visão Geral

- **Propósito:** expor e operar serviços auxiliares para integração com o PagTesouro (solicitação e atualização de pagamentos GRU) e disponibilizar uma interface administrativa de consulta/exportação. O sistema também propaga pagamentos concluídos para o SINGRA em cenários específicos.  
- **Público-alvo:**  
  - **Desenvolvedores:** manutenção de APIs Node/Express e PHP, ajustes de integração e infraestrutura.  
  - **Usuários finais/operadores:** militares/gestores que geram solicitações de pagamento via formulário web.  
  - **Administradores:** equipes de suporte e operações que acompanham pagamentos pela grade “grid/” e executam scripts de teste.  
- **Principais funcionalidades:**  
  - Criação de GRU no PagTesouro via endpoint `/handle`.  
  - Atualização de status de pagamentos via endpoint `/update` com consolidação no banco PostgreSQL e notificação ao SINGRA para a categoria CCIM.  
  - Criptografia de dados sensíveis (nome, CPF/CNPJ) antes de persistir.  
  - Interface web para operadores com grid filtrável e exportação XLSX dos pagamentos.  
  - Script CLI (`papem_test.js`) para testar a geração de GRU PAPEM.

## 2. Arquitetura

### Componentes e interações (alto nível)

- **Frontend de solicitação** (`INDEX/`): formulário PHP/JS que coleta dados de pagamento e chama o backend Node (porta 3000 por HTTPS).  
- **API Node/Express** (`server.js` / `pgt.js`): expõe `/handle` (criar GRU) e `/update` (atualizar status); comunica-se com PagTesouro (HTTPs + proxy), grava/atualiza PostgreSQL e, para CCIM, notifica o SINGRA via HTTPS.  
- **Persistência**: PostgreSQL (`pagtesouro.tb_pgto` e `pagtesouro.tb_servico`).  
- **Interface administrativa** (`grid/`): PHP que consulta PostgreSQL, descriptografa campos e renderiza grid (jqxGrid) com exportação XLSX e botões de atualização/comprovante.  
- **Scripts utilitários**: `papem_test.js` envia requisições de teste PAPEM diretamente ao PagTesouro.

Interação resumida:

```
Formulário PHP -> API Node (/handle) -> PagTesouro -> PostgreSQL
                                          |
                                          └-> (/update) -> PagTesouro -> PostgreSQL -> (CCIM) SINGRA
Interface grid PHP -> PostgreSQL (descriptografa) -> UI jqxGrid/Export
```

### Padrões e decisões
- **Express + middlewares** para roteamento e parsing JSON/URL-encoded.  
- **Separação por endpoint**: `/handle` (criação) e `/update` (atualização/propagação).  
- **Criptografia simétrica (AES-128-CBC)** para nome e CPF/CNPJ antes de persistir.  
- **Camada de segurança HTTP**: HSTS, cache-control no-cache, X-Frame-Options SAMEORIGIN e CORS restritivo.  
- **Uso de proxy HTTPs** para chamadas externas, configurado diretamente na instância axios.  
- **Grid virtualizada** com jqxGrid (filtro, paginação, exportação) consumindo fonte JSON do PHP.

### Stack tecnológica
- **Backend API:** Node.js, Express, body-parser, axios-https-proxy-fix, ssl-root-cas, https/fs, cors, pg, crypto.  
- **Frontend/formulário:** PHP 7+, jQuery 3.5.1, axios, jQuery UI, máscaras, toastify, MonthPicker.  
- **Grid administrativa:** PHP 7+, jqWidgets (jqxGrid), XLSX, Bootstrap.  
- **Banco:** PostgreSQL 11+ (credenciais/host definidos nos arquivos, sugerido uso de variáveis de ambiente).  
- **Infra:** HTTPS com certificados locais (`pagtesouro.key/.pem` ou caminhos absolutos em produção), proxy corporativo (`proxy-1dn.mb:6060`).

### Estrutura de diretórios (principais)
- `/server.js` – API HTTPS (produção) integrada ao PagTesouro/SINGRA.  
- `/pgt.js` – Variante da API com placeholders/sanitizada para tokens/chaves.  
- `/INDEX/` – Formulário de geração de GRU e autenticação.  
- `/grid/` – Interface administrativa (PHP + JS + jqxGrid) e fonte de dados `data2.php`.  
- `/papem_payload.example.json` – Exemplo de payload PAPEM.  
- `/papem_test.js` – Script de teste CLI para GRU PAPEM.  
- `/DGOM_PAGTESOURO.sql`, `/banco-dgom.md` – Artefatos de banco (dump/notas).

## 3. Instalação e Configuração

### Pré-requisitos
- Node.js 14+ e npm/yarn.  
- PHP 7.4+ com extensões `pgsql` e `openssl`.  
- PostgreSQL acessível com schema `pagtesouro` (tabela `tb_pgto` e `tb_servico`).  
- Certificados TLS válidos para o host onde a API Node rodará.  
- Acesso ao proxy corporativo (`proxy-1dn.mb:6060`) e aos endpoints do PagTesouro/SINGRA.

### Passos de instalação
1. Instalar dependências Node (no diretório raiz):  
   ```bash
   npm install express body-parser axios-https-proxy-fix ssl-root-cas cors pg
   ```
2. Provisionar certificados: copie `pagtesouro.key` e `pagtesouro.pem` (ou configure caminhos absolutos em `server.js`/`pgt.js`).  
3. Configurar credenciais e tokens (ver seção Variáveis de Ambiente).  
4. Configurar PHP (Apache/Nginx + PHP-FPM) apontando `INDEX/` e `grid/` conforme vhost.  
5. Criar e popular o schema no PostgreSQL usando `DGOM_PAGTESOURO.sql` (se aplicável) e garantir usuário com permissão de leitura/escrita.

### Variáveis de ambiente / segredos recomendados
Substituir literais nos arquivos por variáveis de ambiente e carregá-las via `process.env`:
- `PAGTESOURO_ENDPOINT` (default produção) e `PAGTESOURO_ENDPOINT_HMG`.  
- `PAGTESOURO_TOKEN`, `PAGTESOURO_TOKEN_CCCPM`, `PAGTESOURO_TOKEN_CCCPM2`, `PAGTESOURO_TOKEN_PAPEM`.  
- `PAGTESOURO_PROXY_AUTH` e `HTTPS_PROXY_HOST/PORT` se diferir do padrão.  
- `PGUSER`, `PGHOST`, `PGDATABASE`, `PGPASSWORD`, `PGPORT`, `PGSCHEMA`.  
- `AES_KEY`, `AES_IV` para criptografia dos campos sensíveis.  
- `SINGRA_BASIC_AUTH` (usuário/senha base64) e `SINGRA_CERT_PATH`.  
- Caminhos de `TLS_KEY_PATH` e `TLS_CERT_PATH`.

### Configurações iniciais
- Ajustar `hmg_ender`/`prd_ender` e `aut` (proxy) em `server.js` ou `pgt.js` conforme ambiente.  
- Garantir que o serviço Node escute em porta 3000 com HTTPS (requer certificados).  
- Validar que o proxy aceita as chamadas externas (PagTesouro e SINGRA).  
- No PHP, configurar `conpg11.php` com as credenciais corretas do PostgreSQL e habilitar sessão segura.

## 4. Documentação da API/Código

### API Node (HTTPS)

#### `POST /handle` (`server.js`)
- **Propósito:** criar uma nova GRU no PagTesouro e persistir metadados.  
- **Entrada:** JSON do PagTesouro contendo, entre outros, `cat`, `nomeUG`, `id_servico`, `codigoServico`, `nomeContribuinte`, `cnpjCpf`, `competencia`, `vencimento`, valores, rubricas e campos adicionais PAPEM.  
- **Processo:**  
  1. Busca sequência `cd_ref_seq` no PostgreSQL por CPF/CNPJ e UG (ou categoria PAPEM).  
  2. Monta `referencia` concatenando UG/categoria + CPF/CNPJ normalizado + sequência.  
  3. Seleciona token conforme `cat_servico`.  
  4. Faz `POST` para `…/solicitacao-pagamento` via axios com proxy.  
  5. Criptografa `nomeContribuinte` e `cnpjCpf` (AES-128-CBC) e insere na tabela `pagtesouro.tb_pgto`.  
- **Saída:** JSON devolvido pelo PagTesouro ou objeto de erro com `situacao.codigo` `CORRIGIR/ERRO`.  
- **Erros comuns:** falta de CPF (`Campo CPF vazio!`), falha de banco, resposta de erro do PagTesouro (códigos em `error.response.data`).【F:server.js†L73-L243】

#### `POST /update` (`server.js`)
- **Propósito:** consultar status de um pagamento no PagTesouro e atualizar registro local; opcionalmente notificar SINGRA para CCIM.  
- **Entrada:** JSON com `id_pgto` e `cat_servico` (usa para decidir token); pode trazer `cd_cpf` para notificação CCIM.  
- **Processo:**  
  1. GET `pagamentos/{id_pgto}` no PagTesouro.  
  2. Atualiza `tb_pgto` com situação/valores retornados.  
  3. Se categoria `CCIM` e status `CONCLUIDO`, envia POST para SINGRA (`/pagtesouro/pagamento`) com basic auth e certificado adicional, então marca `singra_ok = 1`.  
- **Saída:**  
  - `"1"` para update simples,  
  - `["1 ok"]` ou `["1 fail", mensagem]` para CCIM (SINGRA),  
  - `"0"` em erro de consulta.  
- **Erros comuns:** falha de proxy/PagTesouro, erro ao atualizar PostgreSQL, erro SINGRA (retorna `erro_Msg`).【F:server.js†L245-L533】

#### Considerações adicionais
- **Criptografia:** chaves/IV definidos no código; recomenda-se externalizar.  
- **Segurança HTTP:** HSTS, cache-control no-cache, X-Frame-Options SAMEORIGIN, CORS limitado a `127.0.0.1` (ajuste para hosts reais).  
- **HTTPS:** servidor Express criado com certificados locais definidos em `options`.  
- **Proxy:** axios configurado com host `proxy-1dn.mb` porta `6060`, com cabeçalho `Proxy-Autorization`.

### Interface administrativa (PHP)

- **`grid/data2.php`:** serviço JSON consumido pelo jqxGrid. Valida sessão, lê filtros/ordenação, consulta `tb_pgto`, descriptografa `nome` e `cd_cpf` (AES-128-CBC) e permite atualizar `ds_obs` via POST `update=true`. Diferencia consultas para categorias PAPEM/IMH ou UG específicas.【F:grid/data2.php†L1-L195】  
- **`grid/index.php`:** página da grade. Determina colunas/fields por UG, chama `data2.php` via jqxGrid, aciona `/update` da API Node para atualizar status, exporta XLSX com `xlsx.full.min.js` e oferece botão de comprovante (PDF externo). Inclui mensagens de orientação e overlay de loading.【F:grid/index.php†L1-L418】

### Formulário de geração (`INDEX/index.php`)
- Monta seleção de serviços por categoria, coleta dados do contribuinte, aplica máscaras/validação, e envia payload ao backend PagTesouro (mesma porta 3000). Usa sessão PHP e carrega lista de serviços de `pagtesouro.tb_servico`.【F:INDEX/index.php†L1-L200】

### Script CLI PAPEM (`papem_test.js`)
- Lê payload JSON (default `papem_payload.example.json`), injeta `Authorization: Bearer ${PAPEM_TOKEN}` e envia POST para `PAGTESOURO_ENDPOINT` (default VAL). Imprime status/cabeçalhos/resposta; encerra com erro se variáveis ausentes ou payload inválido.【F:papem_test.js†L1-L74】

## 5. Fluxos e Casos de Uso

- **Gerar pagamento (operador):**  
  1. Autentica-se via `INDEX/login.php` e preenche formulário `INDEX/index.php`.  
  2. Frontend envia payload para API `/handle`.  
  3. API cria GRU no PagTesouro, grava `tb_pgto` e retorna situação `CRIADO`.  
  4. Operador pode emitir guia/continuar o fluxo no PagTesouro.

- **Acompanhar status (admin/UG):**  
  1. Acessa `grid/index.php`, que carrega dados via `data2.php`.  
  2. Para registros pendentes, usa botão **Atualizar** → chama `/update` → status/valores persistidos.  
  3. Para status `CONCLUIDO`, botão **Comprovante** gera PDF (fora do escopo deste repo).  
  4. Pode exportar XLSX com todos os campos visíveis.

- **Propagação CCIM para SINGRA:**  
  - Quando `/update` identifica `cat_servico == "CCIM"` e status `CONCLUIDO`, monta objeto `singra` e envia POST HTTPS com basic auth e certificado adicional; marca `singra_ok = 1` em sucesso ou se pagamento já registrado.

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
  - HSTS e anti-cache para respostas Express.  
  - X-Frame-Options SAMEORIGIN (mitiga clickjacking).  
  - CORS restrito (ajustar para domínios reais).  
  - Criptografia AES-128-CBC de nome e CPF/CNPJ antes do storage; descriptografia na leitura.  
  - Sessões PHP obrigatórias na grade e no formulário; redireciona se não autenticado.  
  - Sanitização básica de ordenação em `data2.php` (removendo caracteres perigosos).

- **Considerações/limitações:**  
  - Tokens, chaves e credenciais estão hardcoded em `server.js/pgt.js` no snapshot; migre para variáveis de ambiente/secret manager.  
  - Cert paths são absolutos na produção (`/var/www/html/...`); parametrizar para evitar acoplamento.  
  - CORS atualmente libera apenas `127.0.0.1`; ajustar para frontends reais ou usar lista branca.  
  - Falta retry/backoff para chamadas externas; axios/proxy não tratam timeouts explicitamente.  
  - Logs são console-only; considere centralização e mascaramento de PII antes de registrar.

## 7. Manutenção

- **Executar testes/verificações:** não há suíte automatizada neste repositório. Para a API, execute chamadas manuais (curl/Postman) ou use `papem_test.js` para validar integração com PagTesouro PAPEM.  
- **Rodar localmente a API:**  
  ```bash
  node server.js   # requer certificados e variáveis definidas
  # ou
  node pgt.js      # variante com placeholders
  ```
- **Deploy (sugestão):**  
  - Provisionar serviço systemd ou container para Node na porta 3000 com certificados montados.  
  - Configurar vhost/proxy reverso HTTPS apontando para o serviço Node.  
  - Publicar `INDEX/` e `grid/` em servidor PHP com acesso ao mesmo PostgreSQL.  
  - Garantir variáveis secretas em ambiente (tokens, DB, AES, proxy).  
  - Validar conectividade a PagTesouro/SINGRA via proxy antes de produção.

- **Troubleshooting comum:**  
  - **Erro “Campo CPF vazio!”**: payload sem `cnpjCpf`.  
  - **Erros PagTesouro (CORRIGIR/ERRO):** revisar tokens, proxy, endpoint (`hmg_ender` vs `prd_ender`) e corpo enviado.  
  - **SINGRA não responde:** verificar certificado `recim-chain.pem`, basic auth, firewall/proxy, e logs em `/update`.  
  - **Grid sem dados:** checar sessão PHP, credenciais `conpg11.php`, chave/IV de descriptografia e permissões da tabela.  
  - **Exportação XLSX vazia:** confirmar `dataAdapter.records` preenchido e ausência de filtros limitantes.

- **Guia de contribuição:**  
  - Padronizar configurações sensíveis em variáveis de ambiente.  
  - Evitar commits contendo tokens/segredos reais.  
  - Adicionar testes de integração (ex.: mocks do PagTesouro) antes de mudanças de contrato.  
  - Manter logs limpos e sem PII; usar níveis de log adequados.  
  - Ao alterar o schema do PostgreSQL, versionar migrations e atualizar `banco-dgom.md`/scripts SQL.  
  - Seguir padrões existentes de criptografia (AES-128-CBC) e cabeçalhos de segurança ao criar novos endpoints.
