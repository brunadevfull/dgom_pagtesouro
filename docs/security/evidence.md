# Evidências de Código

> Observação: eu tirei os trechos de `server.js` com base nas linhas exibidas durante a inspeção.

## EV-01 — Tokens/segredos hardcoded (PagTesouro)
- **Arquivo:** `server.js`
- **Linhas:** 84–105
- **Trecho:**
  ```js
  var hmg_ender = 'https://valpagtesouro.tesouro.gov.br/api/gru/';
  var hmg_proxy_aut = 'xxxxxxxxxxx; // proxy auth hardcoded
  var prd_ender = 'https://pagtesouro.tesouro.gov.br/api/gru/';
  var tokenAcesso = "xxxxx";
  var tokenAcesso_old = "xxxxxx";
  var tokenAcesso = "xxx";
  var tokenAcessoCCCPM = "xxxxxxxxxxxxxx";
  var tokenAcessoCCCPM2 = "xxxxxxxxxxxxxx";
  var tokenAcessoPAPEM = " xxxxxxxx";
  ```

## EV-02 — Credenciais de banco hardcoded
- **Arquivo:** `server.js`
- **Linhas:** 159–166
- **Trecho:**
  ```js
  const pool = new Pool({
    user: 'xxxx',
    host: 'xxxxx',
    database: 'xxxx',
    schema: 'xxxx',
    password: 'xxxx',
    port: 5432
  })
  ```

## EV-03 — Basic Auth hardcoded para SINGRA
- **Arquivo:** `server.js`
- **Linhas:** 405–415
- **Trecho:**
  ```js
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Basic ' + new Buffer.from('admin' + ':' + 'pwssingra').toString('base64')
  },
  ```

## EV-04 — Endpoints sem autenticação
- **Arquivo:** `server.js`
- **Linhas:** 107–114 e 313–318
- **Trecho:**
  ```js
  app.post('/handle', cors(corsOptions), (request,response) => {
    ...
  })

  app.post('/update', cors(corsOptions),(request,response) => {
    ...
  })
  ```

## EV-05 — Log de payloads (PII)
- **Arquivo:** `server.js`
- **Linhas:** 111–114
- **Trecho:**
  ```js
  console.log(request.body);
  ```

## EV-06 — Log de dados criptografados
- **Arquivo:** `server.js`
- **Linhas:** 259–260
- **Trecho:**
  ```js
  geralog("Nome criptografado: " + nome_encrypted);
  geralog("CPF/CNPJ criptografado: " + cnpjCpf_encrypted);
  ```

## EV-07 — AES-CBC com IV fixo
- **Arquivo:** `server.js`
- **Linhas:** 237–253
- **Trecho:**
  ```js
  key = Buffer.from("xxxxx",'utf8');
  iv = Buffer.from('xxxxxxx','utf8');
  var cipher = crypto.createCipheriv('aes-128-cbc', key, iv);
  ```

## EV-08 — Validação de entrada mínima
- **Arquivo:** `server.js`
- **Linhas:** 181
- **Trecho:**
  ```js
  if (request.body.cnpjCpf == '') throw "Campo CPF vazio!";
  ```

## EV-09 — Comentário sobre TLS inseguro
- **Arquivo:** `server.js`
- **Linhas:** 399
- **Trecho:**
  ```js
  //process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
  ```
