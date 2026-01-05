# Relatório de Segurança (Análise Estática)

> Eu escrevi este relatório no meu tom direto, como se fosse um registro pessoal do que eu vi no código.

## 1. Escopo da Análise
- **O que eu analisei:** o código dos serviços Node.js que cuidam dos endpoints e integrações externas (`server.js` e `pgt.js`) e o script auxiliar `papem_test.js`.
- **O que eu não analisei:** execução do sistema, infraestrutura real (certificados, proxy, DB), configurações de ambiente em produção e dependências externas minificadas em `grid/`.
- **Premissas que usei:** análise estritamente baseada no que está no repositório; o comportamento real pode variar conforme configurações externas.

## 2. Visão Geral de Segurança
- **Nível geral de maturidade (na minha leitura):** **médio-baixo**.
- **Principais superfícies de ataque que eu identifiquei:**
  - Endpoints expostos via Express (`/handle` e `/update`) sem autenticação explícita.
  - Integrações externas (PagTesouro, SINGRA) com credenciais em código.
  - Registro de dados sensíveis em logs.
  - Criptografia aplicada com chaves/IV fixos e sem autenticação.

## 3. Vulnerabilidades Confirmadas

### VULN-01 — Segredos hardcoded em código
- **Categoria:** OWASP A02 – Cryptographic Failures / A07 – Identification and Authentication Failures
- **Descrição técnica (em português claro):** tokens, credenciais de DB e Basic Auth estão embutidos no código-fonte.
- **Evidência:** ver `docs/security/evidence.md` (EV-01, EV-02, EV-03).
- **Impacto:**
  - Confidencialidade: **Alta**
  - Integridade: **Alta**
  - Disponibilidade: **Média**
- **Severidade:** **Alta**
- **Cenário plausível (do jeito que eu vejo):** se alguém cair em cima do repositório/backup, já sai com tokens e credenciais na mão e consegue usar APIs e banco como quiser.
- **Mitigação:**
  - Curto prazo: mover segredos para variáveis de ambiente/secret manager e rotacionar tokens.
  - Médio prazo: vault com rotação automática e políticas de menor privilégio.

### VULN-02 — Ausência de autenticação/autorização nos endpoints críticos
- **Categoria:** OWASP A01 – Broken Access Control
- **Descrição técnica (direto ao ponto):** `/handle` e `/update` aceitam chamadas sem autenticação/ACL.
- **Evidência:** ver `docs/security/evidence.md` (EV-04).
- **Impacto:**
  - Confidencialidade: **Média**
  - Integridade: **Alta**
  - Disponibilidade: **Média**
- **Severidade:** **Alta**
- **Cenário plausível:** qualquer cliente que chegue na porta do serviço pode criar ou atualizar pagamentos.
- **Mitigação:**
  - Curto prazo: exigir autenticação (API key/JWT/mTLS) e restringir origem por rede.
  - Médio prazo: RBAC e auditoria de chamadas.

### VULN-03 — Exposição de dados sensíveis em logs
- **Categoria:** OWASP A09 – Security Logging and Monitoring Failures / A02 – Cryptographic Failures
- **Descrição técnica:** logs imprimem payloads completos, respostas externas e dados criptografados, incluindo PII (CPF/CNPJ, nome).
- **Evidência:** ver `docs/security/evidence.md` (EV-05, EV-06).
- **Impacto:**
  - Confidencialidade: **Alta**
  - Integridade: **Baixa**
  - Disponibilidade: **Baixa**
- **Severidade:** **Média**
- **Cenário plausível:** se alguém tiver acesso aos logs, vai ver PII e dados de transação.
- **Mitigação:**
  - Curto prazo: mascarar PII e reduzir verbosidade em produção.
  - Médio prazo: política de logging, classificação de dados e retenção mínima.

### VULN-04 — Criptografia fraca (IV fixo, AES-CBC sem integridade)
- **Categoria:** OWASP A02 – Cryptographic Failures
- **Descrição técnica:** uso de AES-CBC com chave/IV fixos e sem autenticação (MAC/AEAD).
- **Evidência:** ver `docs/security/evidence.md` (EV-07).
- **Impacto:**
  - Confidencialidade: **Alta**
  - Integridade: **Média**
  - Disponibilidade: **Baixa**
- **Severidade:** **Alta**
- **Cenário plausível:** a cifragem determinística deixa padrões visíveis e ainda dá margem para mexer em blocos.
- **Mitigação:**
  - Curto prazo: IV aleatório por registro e armazenamento junto ao ciphertext.
  - Médio prazo: adotar AES-GCM/ChaCha20-Poly1305 e rotação de chaves.

## 4. Riscos Potenciais / Más Práticas
- **CORS permissivo/inconsistente:** `Access-Control-Allow-Origin` é fixado, mas `app.use(cors())` sem restrições pode permitir origens indevidas (depende do deploy). Ver EV-08.
- **Validação de entrada mínima:** apenas validação de CPF vazio; demais campos seguem direto para processamento/DB. Ver EV-09.
- **Comentário de TLS inseguro:** existe instrução comentada para desabilitar validação TLS (`NODE_TLS_REJECT_UNAUTHORIZED`). Ver EV-10.
- **Ausência de rate limiting:** não há controle de volume de requisições (risco de abuso/DoS).

## 5. Segurança por Camada
- **Autenticação:** ausente nos endpoints críticos (EV-04).
- **Autorização:** sem controle por perfis/ownership.
- **Validação de entrada:** mínima (EV-09).
- **Criptografia e segredos:** chaves/IV fixos e segredos em código (EV-01, EV-07).
- **Logs e dados sensíveis:** exposição de PII (EV-05, EV-06).
- **Comunicação externa:** uso de tokens/Basic Auth hardcoded; validação TLS depende de configuração externa (EV-02, EV-03).
- **Infraestrutura/deploy:** dependência de arquivos locais de chave/certificado, sem evidência de rotação.

## 6. Recomendações Priorizadas
- **Quick wins:**
  1. Remover segredos do código (variáveis de ambiente/secret manager).
  2. Implementar autenticação nos endpoints (`/handle`, `/update`).
  3. Reduzir logs de PII e dados transacionais.
- **Melhorias estruturais:**
  1. Adotar criptografia autenticada (AES-GCM/ChaCha20-Poly1305).
  2. Validar payloads via schema (ex.: Joi/Zod) e normalizar dados.
  3. Restringir CORS por lista explícita de origens.
- **Dívida técnica:**
  1. Política de rotação de chaves/tokens e gestão centralizada.
  2. Monitoramento e rate limiting.
  3. Revisão contínua de dependências e bibliotecas externas.

## 7. Limitações da Análise
- Não houve execução do sistema nem inspeção de ambiente real.
- Dependências minificadas em `grid/` não foram analisadas.
- Não foi avaliada a configuração de proxy/certificados reais.
