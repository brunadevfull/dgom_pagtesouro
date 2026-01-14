# Resumo rápido (1 página A4) — reunião DGOM

## 1) Por que isso importa (bem direto)
Hoje o sistema tem pontos que deixam dados sensíveis e o fluxo de pagamento expostos. O risco maior é alguém conseguir chamar os endpoints, vazar dados ou usar credenciais do próprio sistema. Aqui tá o que precisa ser corrigido primeiro.

## 2) Top 4 problemas (prioridade alta)
1. **Segredos hardcoded no código**
   - Tokens, credenciais de DB e Basic Auth estão dentro do código.
   - Impacto: acesso não autorizado a APIs e banco, além de risco de vazamento.
2. **Endpoints sem autenticação**
   - `/handle` e `/update` aceitam chamadas sem autenticação.
   - Impacto: qualquer um na rede consegue criar ou atualizar pagamentos.
3. **Logs com dados pessoais**
   - Logs imprimem payloads completos e dados sensíveis (CPF/CNPJ, nome).
   - Impacto: vazamento de PII em logs internos.
4. **Criptografia fraca (AES-CBC com IV fixo)**
   - Chaves/IV fixos e sem autenticação de integridade.
   - Impacto: padrões visíveis e risco de manipulação.

## 3) O que mudar primeiro (quick wins)
- **Mover segredos pra variáveis de ambiente / secret manager**  
  (e rotacionar tokens atuais).
- **Colocar autenticação nos endpoints `/handle` e `/update`**  
  (API key, JWT ou mTLS).
- **Reduzir logs com PII**  
  (mascarar CPF/CNPJ e remover payload completo).

## 4) O que vem logo depois (curto prazo)
- **Criptografia autenticada**  
  trocar AES-CBC por AES-GCM/ChaCha20-Poly1305 com IV aleatório.
- **Validação de payloads**  
  schema (Joi/Zod) e normalização (CPF/CNPJ, datas, valores).
- **Rate limiting**  
  controle básico de volume pra evitar abuso.

## 5) Evidências (se pedirem na reunião)
- Segredos hardcoded no código.
- Endpoints sem autenticação.
- Logs com PII.

Se quiser, na reunião eu abro o código só pra mostrar 1 ou 2 trechos e confirmar o risco — mas o foco é fechar o plano de ação.
