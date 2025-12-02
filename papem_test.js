#!/usr/bin/env node
/**
 * Script simples para emitir a requisição de criação de GRU na PAPEM.
 *
 * Uso:
 *   PAPEM_TOKEN="token_aqui" \ 
 *   PAGTESOURO_ENDPOINT="https://valpagtesouro.tesouro.gov.br/api/gru/solicitacao-pagamento" \ 
 *   node papem_test.js [caminho_para_payload.json]
 *
 * - O token deve ser o mesmo usado no ambiente PAPEM.
 * - O endpoint padrão aponta para o ambiente de validação (VAL). Troque para
 *   produção se necessário: https://pagtesouro.tesouro.gov.br/api/gru/solicitacao-pagamento
 * - Por padrão o script usa o arquivo papem_payload.example.json. Passe um caminho
 *   como argumento para usar outro payload.
 */

const fs = require('fs');
const https = require('https');

const token = process.env.PAPEM_TOKEN;
if (!token) {
  console.error('Defina a variável de ambiente PAPEM_TOKEN com o token do PagTesouro.');
  process.exit(1);
}

const endpoint = process.env.PAGTESOURO_ENDPOINT ||
  'https://valpagtesouro.tesouro.gov.br/api/gru/solicitacao-pagamento';

const payloadPath = process.argv[2] || 'papem_payload.example.json';
let payload;
try {
  const payloadRaw = fs.readFileSync(payloadPath, 'utf-8');
  payload = JSON.parse(payloadRaw);
} catch (err) {
  console.error(`Não foi possível ler ou interpretar o arquivo de payload: ${payloadPath}`);
  console.error(err.message);
  process.exit(1);
}

const data = JSON.stringify(payload);
const url = new URL(endpoint);

const requestOptions = {
  method: 'POST',
  hostname: url.hostname,
  path: url.pathname,
  protocol: url.protocol,
  port: url.port || (url.protocol === 'https:' ? 443 : 80),
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Content-Length': Buffer.byteLength(data)
  }
};

console.log('Endpoint........:', endpoint);
console.log('Payload origem...:', payloadPath);

const req = https.request(requestOptions, (res) => {
  const chunks = [];

  res.on('data', (chunk) => chunks.push(chunk));

  res.on('end', () => {
    const body = Buffer.concat(chunks).toString();
    console.log('Status HTTP.....:', res.statusCode);
    console.log('Cabeçalhos......:', res.headers);
    console.log('Resposta........:', body);
  });
});

req.on('error', (error) => {
  console.error('Erro ao enviar requisição:', error.message);
});

req.write(data);
req.end();
