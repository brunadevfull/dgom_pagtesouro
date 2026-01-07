# Consulta autenticada de pagamentos

Este serviço expõe o endpoint `GET /payments/:id` para consultar pagamentos gravados em `pagtesouro.tb_pgto`. Ele retorna o registro completo com campos sensíveis descriptografados.

## Autenticação
- É obrigatório enviar um token via cabeçalho `Authorization: Bearer <token>` **ou** `x-api-key: <token>`.
- O valor do token é lido da variável de ambiente `API_TOKEN` (ou `BEARER_TOKEN`). Sem esse valor configurado, o acesso é negado.

## Como funciona a consulta
1. A rota lê o parâmetro `:id` da URL e executa `SELECT * FROM pagtesouro.tb_pgto WHERE id_pgto = $1` usando o `Pool` do `pg`.
2. Os campos `nome` e `cd_cpf` são descriptografados com a mesma chave, IV e algoritmo já utilizados na gravação (valores definidos diretamente no código, no mesmo formato base64).
3. O JSON de resposta inclui todos os campos retornados pelo banco, substituindo `nome` e `cd_cpf` pelas versões em texto claro.

## Tratamento de erros e logs
- Chamadas sem token válido recebem `401 Não autorizado` com log `Tentativa de acesso não autorizada.`
- Caso o `id_pgto` não seja encontrado, retorna `404 Pagamento não encontrado` e registra no log.
- Falhas de descriptografia ou consulta são tratadas com `500` e um log detalhando o erro.
- Os logs são emitidos pela função `geralog`, prefixados pelo timestamp local.

## Dicas de uso local
- Defina `API_TOKEN` (ou `BEARER_TOKEN`) e as credenciais de banco (`dbConfig` em `pgt.js`) antes de iniciar o servidor.
- Inicie a aplicação com `node pgt.js` e faça a chamada, por exemplo:
  ```bash
  curl -k -H "Authorization: Bearer $API_TOKEN" https://localhost:3000/payments/123
  ```
- O servidor responde em HTTPS na porta `3000`, utilizando os certificados configurados em `pagtesouro.key` e `pagtesouro.pem`.
