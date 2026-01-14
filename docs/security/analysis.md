# Relatório de Segurança (Análise Estática)

> Escrevi isso no meu tom direto, do jeito que eu registraria o que vi no código.

## 1. Escopo da Análise
- **O que eu analisei:** o serviço Node.js que cuida dos endpoints e integrações externas (`server.js`) e o script auxiliar `papem_test.js`.
- **O que eu não analisei:** execução do sistema, infraestrutura real (certificados, proxy, DB), configs de ambiente em produção e dependências externas minificadas em `grid/`.
- **Premissas que usei:** análise baseada no que tá no sistema da RECIM; o comportamento real pode variar conforme configs externas.

## 2. Visão Geral de Segurança
- **Nível geral de maturidade (na minha leitura):** **médio-baixo**.
- **Principais superfícies de ataque que eu vi:**
  - Endpoints expostos via Express (`/handle` e `/update`) sem autenticação explícita.
  - Integrações externas (PagTesouro, SINGRA) com credenciais em código.
  - Registro de dados sensíveis em logs.
  - Criptografia aplicada com chaves/IV fixos e sem autenticação.

## 3. Vulnerabilidades Confirmadas

### VULN-01 — Segredos hardcoded em código
- **Categoria:** OWASP A02 – Cryptographic Failures / A07 – Identification and Authentication Failures
- **Descrição técnica (em português claro):** tokens, credenciais de DB e Basic Auth estão embutidos no código.
- **Evidência:** ver `docs/security/evidence.md` (EV-01, EV-02, EV-03).
- **Impacto:**
  - Confidencialidade: **Alta**
  - Integridade: **Alta**
  - Disponibilidade: **Média**
- **Severidade:** **Alta**
- **Cenário plausível (do jeito que eu vejo):** se alguém cair em cima de um backup do sistema, já sai com tokens e credenciais na mão e consegue usar APIs e banco como quiser.
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
- **Validação de entrada mínima:** só valida CPF vazio; o resto vai direto pro processamento/DB. Ver EV-08.
- **Comentário de TLS inseguro:** tem instrução comentada pra desabilitar validação TLS (`NODE_TLS_REJECT_UNAUTHORIZED`). Ver EV-09.
- **Ausência de rate limiting:** não há controle de volume de requisições (risco de abuso/DoS).

## 5. Segurança por Camada
- **Autenticação:** ausente nos endpoints críticos (EV-04).
- **Autorização:** sem controle por perfis/ownership.
- **Validação de entrada:** mínima (EV-08).
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
  3. Restringir logs de PII e dados transacionais em ambientes não controlados.
- **Dívida técnica:**
  1. Política de rotação de chaves/tokens e gestão centralizada.
  2. Monitoramento e rate limiting.
  3. Revisão contínua de dependências e bibliotecas externas.

## 7. Limitações da Análise
- Não houve execução do sistema nem inspeção de ambiente real.
- Dependências minificadas em `grid/` não foram analisadas.
- Não foi avaliada a configuração de proxy/certificados reais.
