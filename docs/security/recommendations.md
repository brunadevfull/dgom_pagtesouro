# Recomendações Priorizadas

> Aqui eu deixei o que eu faria primeiro, depois o que exige mais trabalho, e por fim o que fica como dívida técnica.

## Quick wins (baixo esforço, alto impacto)
1. **Remover segredos do código**
   - Eu recomendo substituir tokens, senhas e credenciais hardcoded por variáveis de ambiente ou secret manager.
   - Rotacionar tokens já expostos no repositório.
2. **Autenticar endpoints críticos**
   - Proteger `/handle` e `/update` com API key, JWT ou mTLS.
   - Restringir por IP/rede quando aplicável.
3. **Reduzir logging de PII**
   - Mascarar CPF/CNPJ e nomes, evitando prints do payload completo.

## Melhorias estruturais
1. **Criptografia autenticada**
   - Migrar de AES-CBC com IV fixo para AES-GCM ou ChaCha20-Poly1305.
   - Gerar IV aleatório por registro e armazená-lo junto ao ciphertext.
2. **Validação robusta de entrada**
   - Usar schemas (ex.: Joi/Zod) e rejeitar payloads inválidos.
   - Normalizar campos (CPF/CNPJ, datas, valores).
3. **Política CORS restritiva**
   - Definir lista explícita de origens e métodos permitidos.

## Dívida técnica de segurança
1. **Gestão centralizada de segredos**
   - Implementar vault com rotação automática e auditoria.
2. **Rate limiting e proteção contra abuso**
   - Middleware de rate limiting e detecção de anomalias.
3. **Revisão de dependências**
   - Inventário de dependências e varredura periódica de CVEs.
