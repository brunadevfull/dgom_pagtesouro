 //PAGTESOURO PRODUÇÃO - NOVO
const express = require('express');
const bodyParser = require("body-parser");
const axios = require("axios-https-proxy-fix");
var rootCas = require('ssl-root-cas').create();
const fs = require('fs');
const https = require('https');
var cont = 0;
const router = express.Router();
var cors = require('cors');
const app = express();
const port = 3000;
https.globalAgent.options.ca = rootCas;


// SEGURANÇA => HSTS //
 app.use(function(req, res, next) {
if (req.secure) {
res.setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
}
next();
}) 

// SEGURANÇA => CACHE CONTROL //
app.use((req, res, next) => {
  res.setHeader('Cache-Control', 'no-cache, no-store');
  res.setHeader('Pragma', 'no-cache');
  next();
})

// SEGURANÇA => CABEÇALHO XFO //
app.use((req, res, next) => {
  // Define o cabeçalho X-Frame-Options como "SAMEORIGIN"
  res.setHeader('X-Frame-Options', 'SAMEORIGIN');
  next();
});

// Middleware para habilitar o CORS FALTA TESTAR
app.use((req, res, next) => {
res.setHeader('Access-Control-Allow-Origin', '127.0.0.1');
	res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT');
	res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
	res.setHeader('Access-Control-Allow-Credentials', 'true');
	next();
});

app.use(cors());
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());

var options = {
    key: fs.readFileSync('pagtesouro.key'),
    cert: fs.readFileSync('pagtesouro.pem')
};

var corsOptions = {
  origin: false
}


/**
 * Registra mensagens com carimbo de data e hora para facilitar auditoria e depuração.
 *
 * @param {string} texto - Texto a ser registrado no console.
 * @returns {void}
 * @example
 * geralog('Servidor iniciado');
 */
function geralog(texto) {
        console.log(new Date().toLocaleString() + " - " + texto);
}


//CONFIGURAÇÃO DOS AMBIENTES	
	var hmg_ender = 'https://valpagtesouro.tesouro.gov.br/api/gru/'; 
	var hmg_proxy_aut = 'Basic MTU2NTgxMTk3NjY6QXBsaWNhc2lwbGEyMDIxQA==';
	var prd_ender = 'https://pagtesouro.tesouro.gov.br/api/gru/';
	
	var ender;	
	ender = prd_ender;
	aut = hmg_proxy_aut;

var tokenAcesso = "#";
        var tokenAcessoCCCPM = "#";
        var tokenAcessoCCCPM2 = "#";
        var tokenAcessoPAPEM = "#";

/**
 * Endpoint responsável por receber os dados brutos de geração da GRU,
 * calcular o código de referência, registrar no banco e solicitar a criação
 * do pagamento no PagTesouro.
 *
 * @param {express.Request} request - Requisição HTTP contendo o corpo com os dados da GRU.
 * @param {express.Response} response - Resposta HTTP enviada ao consumidor da API.
 * @returns {void}
 * @example
 * // Envio de GRU com curl
 * // curl -X POST https://localhost:3000/handle -H "Content-Type: application/json" -d '{"cnpjCpf":"12345678901","cat":"CCIM"}'
 */
app.post('/handle', cors(corsOptions), (request,response) => {

    geralog(" Dados para GRU Recebidos!");
    geralog("======= INÍCIO DOS DADOS ORIGINAIS ========");
    console.log(request.body);
    geralog("======= FIM DOS DADOS ORIGINAIS ========");

        var value = request.body;
        var ug = "";
	
	
        var cd_ref_seq;
        var cd_referencia;

                /**
                 * Monta o código de referência numérico da GRU considerando a sequência
                 * para o contribuinte e possíveis ajustes por categoria.
                 *
                 * @param {number} result - Sequencial retornado do banco que será incrementado.
                 * @returns {void}
                 * @example
                 * montaref(3); // ajusta value.referencia com padding e dados da UG
                 */
                function montaref(result) {
                        //montagem do codigo de referencia da GRU
				cd_referencia = result.toString();
				while (cd_referencia.length < 4) cd_referencia = "0" + cd_referencia;				
				
				var cnpjCpf_edit = value.cnpjCpf;
				
				//caso seja um CNPJ, retiramos os 3 primeiros dígitos do mesmo para "caber" no cumprimento do número de referencia.    Entao se o cnpj é 00394502009958,   apenas 94502009958 será considerado
				if (cnpjCpf_edit.length == 14) cnpjCpf_edit = cnpjCpf_edit.substring(3);
				//if (cnpjCpf_edit.length == 14) cnpjCpf_edit = cnpjCpf_edit.replace("000","");
				
					if (value.cat == "PAPEM") 
						value.referencia = "73200" + cnpjCpf_edit + cd_referencia;
					else
						value.referencia = value.nomeUG + cnpjCpf_edit + cd_referencia;			
					//aqui o value.nome recebe o ARRECADADOR.  mas guardamos a UG selecionada para gravar no banco. a variável UG será repassada na query de insert.
						ug = value.nomeUG;
						value.nomeUG = "673006";	
					
				if (ug == '78001') value.nomeUG = "778001";			
				if (ug == '78000') value.nomeUG = "778000";	
								
				if (value.cat == "PAPEM") 
				//temos uma requisição específica da PAPEM. ALGUNS AJUSTES DEVEM SER FEITOS
				// 1 - o campo UG, copiado acima, vem com a SIGLA da OM
				// 2 - ALGUNS CAMPOS A MAIS SÃO ENVIADOS. O INSERT DEVE SER MODIFICADO
				{ 
				  value.cat_servico = "PAPEM";
				  value.nomeUG = "773200";
				  value.id_servico = "52";	//serviço genérico da PAPEM	  //adaptação para receber requisições da PAPEM.  //no futuro, fazer id_servico = codigo_servico
				}
						
			}
			
			const { Pool } = require('pg')								
			const pool = new Pool({
			  user: '#',
			  host: '#',
			  database: '#',
			  schema: '#',
			  password: '#',
			  port: 111111
			})
			
			// PRIMEIRO BLOCO ASYNC
			try {
				geralog ("Consulta para montagem do sequencial...");		
			
				//POSTGRES
				if (request.body.cat == "PAPEM") {
				var query = "SELECT COALESCE (MAX(CD_REF_SEQ), 0) AS seq  FROM pagtesouro.tb_pgto where cd_cpf = $1 and cat_servico = 'PAPEM' "
				var values = [request.body.cnpjCpf];
				} else {
				var query = "SELECT COALESCE (MAX(CD_REF_SEQ), 0) AS seq  FROM pagtesouro.tb_pgto where cd_cpf = $1 and cd_om = $2 "
				var values = [request.body.cnpjCpf, request.body.nomeUG];	
				}
				if (request.body.cnpjCpf == '') throw "Campo CPF vazio!";
				
				// callback	
				pool.query(query, values, (err, res) => {
				if (err) throw (new Date().toLocaleString()+" Erro no registro!");																		  
				  console.log("POSTGRES RETORNOU SEQ = " + res.rows[0].seq); 
				  //função para montagem do código de referência				
					montaref(res.rows[0].seq + 1);	
					//CODIGO CONTINUA AQUI
					enviapost();
													
				  });
					
			} catch (error) { console.log (error) }
			
			
                        /**
                         * Envia os dados já preparados ao PagTesouro e aciona o registro no banco.
                         * Responsável por escolher o token correto de acordo com a categoria e
                         * invocar o fluxo assíncrono de criação da GRU.
                         *
                         * @returns {void}
                         * @example
                         * enviapost();
                         */
                        function enviapost(){
					
					geralog(" Dados para GRU Recebidos!");
					geralog("======= INÍCIO DOS DADOS A ENVIAR ========");
					console.log(value);
					geralog("======= FIM DOS DADOS A ENVIAR ========");
					var token = tokenAcesso;					
					if (value.cat_servico == "CCCPM") token = tokenAcessoCCCPM;
					if (value.cat_servico == "CCCPM2") token = tokenAcessoCCCPM2;
					if (value.cat_servico == "PAPEM") token = tokenAcessoPAPEM; 
					
					//fazendo post request
					const options = {
						headers: { 'Authorization': 'Bearer ' + token,
								'Proxy-Autorization': aut},
						proxy: {
						host: 'proxy-1dn.mb',
						port: 6060
						
					 }
					};
					
					geralog(" Emitindo POST-REQUEST para " + ender  + 'solicitacao-pagamento');
					cont = cont + 1;	
					
                                        //SEGUNDO BLOCO AYNC
                                        /**
                                         * Chama o serviço de criação de pagamento do PagTesouro
                                         * e grava o retorno no banco de dados. Em caso de erro,
                                         * formata e devolve uma resposta amigável ao consumidor.
                                         *
                                         * @returns {Promise<void>}
                                         * @example
                                         * await sendPost();
                                         */
                                        const sendPost = async () => {
					try {
						
						const response2 = await axios.post(ender + 'solicitacao-pagamento', value , options);
						geralog("Resposta PagTesouro recebida!");
						geralog("======= INÍCIO DA RESPOSTA ========");
						console.log(response2.data);
						var resposta = response2.data;    			
						geralog("======= FIM DA RESPOSTA ========");
						if (resposta["situacao"]["codigo"] == "CRIADO") {
						geralog("Sucesso na criação da GRU. ID Pagamento: " + resposta["idPagamento"]);       				
						geralog("Iniciando registro no Banco de Dados...");    
							
							
						//precisamos CRIPTOGRAFAR NOME E CPF!
						key = Buffer.from("#",'utf8');
						iv_size = '#';
						iv = Buffer.from('#','utf8');							
							
						var crypto  = require( 'crypto' );
							
						var cipher  = crypto.createCipheriv( '#', key, iv );
						var nome_encrypted = cipher.update(value.nomeContribuinte, 'utf8','base64');
						nome_encrypted += cipher.final('base64');
						cipher  = crypto.createCipheriv( '#', key, iv );
						var cnpjCpf_encrypted = cipher.update(value.cnpjCpf, 'utf8','base64');
						cnpjCpf_encrypted += cipher.final('base64');
							
						geralog("Nome criptografado: " + nome_encrypted);
						geralog("CPF/CNPJ criptografado: " + cnpjCpf_encrypted);
							
						//POSTGRES
						var query = "INSERT INTO pagtesouro.tb_pgto (id_pgto, id_servico, dt_criacao, ds_situacao, dt_situacao, cd_servico, cd_om, dt_vencimento, dt_competencia, nome, cd_cpf, vr_descontos, vr_deducoes, vr_multa, vr_juros, vr_oacresc, vr_principal, cd_referencia, cd_ref_seq, cat_servico, cod_rubrica, nome_rubrica, motivo, tributavel, nomeoc, vr_bruto_ex_ant, vr_ex_atu, natdev, cod_siapenip, cod_oc, cod_om ) VALUES ($1, $2 , TO_TIMESTAMP($3,'YYYY-MM-DD\"T\"HH24:MI:SSZ') ,$4 ,TO_TIMESTAMP( $5,'YYYY-MM-DD\"T\"HH24:MI:SSZ') , $6,  $7 ,TO_DATE($8,'DDMMYYYY') ,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$20,$21,$22,$23,$24,$25,$26,$27,$28,$29,$30,$31)"
						var values = [resposta.idPagamento,value.id_servico,resposta.dataCriacao,resposta.situacao.codigo,resposta.dataCriacao,value.codigoServico,ug,value.vencimento,value.competencia,nome_encrypted,cnpjCpf_encrypted,(value.valorDescontos || 0), (value.valorOutrasDeducoes || 0),(value.valorMulta || 0),(value.valorJuros || 0),(value.valorOutrosAcrescimos || 0),(value.valorPrincipal || 0),value.referencia,cd_referencia,value.cat_servico,(value.codRubrica || "N/A"),(value.nomeRubrica || "N/A"),(value.motivo || "N/A" ),(value.tributavel || 0),(value.nomeOC || "N/A" ),(value.valorBrutoExercAnt || null),(value.valorExercAtual || null),(value.NatDev || "N/A" ),(value.codSiapeNip || "N/A" ),(value.cod_oc || "N/A" ),(value.cod_om || "N/A" )] // 
							 
						//callback
						//pool.query(query, [request.body.id_pgto](err, res) => {
						pool.query(query, values, (err, res) => {
						if (err) throw (new Date().toLocaleString()+" Erro no registro!");														
						geralog("Registro no Banco de Dados efetuado com sucesso! (POSTGRES)");							  
						try {
							response.send(resposta);	
							} catch (error) {
								geralog("Erro: " + error);
							}								
						});
								
						} 
						
						} catch (error) {
							var erro = new Object();
						
						//console.error(typeof(error.response));
						if (typeof(error.response)!=='undefined'){

						geralog("Acesso: " + cont + " - Resposta do REQUEST com Erro!");
						console.log(error.response.data);
						for (const [key,value] of Object.entries(error.response.data)) {
							geralog("Erro: " + value["codigo"] + " - " + value["descricao"]);	
							erro[value["codigo"]] = value["descricao"];
						}
							erro["situacao"] = { codigo: 'CORRIGIR' };
							geralog("Enviada resposta ao usuário."); 
							console.log(erro);
							response.send(erro);
							
						} else {
							erro["situacao"] = { codigo: 'ERRO' };
							console.log(error);
							response.send(erro);
							}
					}
					
					};	
					
					sendPost();
					
			}		
	
});


/**
 * Endpoint responsável por consultar o PagTesouro para um idPagamento
 * específico e refletir o status no banco de dados, disparando integrações
 * adicionais quando necessário.
 *
 * @param {express.Request} request - Requisição contendo o id_pgto e dados complementares.
 * @param {express.Response} response - Resposta HTTP com o status da operação.
 * @returns {void}
 * @example
 * // curl -X POST https://localhost:3000/update -H "Content-Type: application/json" -d '{"id_pgto":"123","cat_servico":"CCIM"}'
 */
app.post('/update', cors(corsOptions),(request,response) => {

        geralog("Solicitação Recebida! Atualizar idPagamento : " + request.body.id_pgto);
        console.log(request.body);
	
	token = tokenAcesso;
	if (request.body.cat_servico == "CCCPM") token = tokenAcessoCCCPM;
	if (request.body.cat_servico == "CCCPM2") token = tokenAcessoCCCPM2;
	if (request.body.cat_servico == "PAPEM")  token = tokenAcessoPAPEM;
				
	
	//fazendo get request
	const options = {
		headers: { 'Authorization': 'Bearer ' + token,
				'Proxy-Autorization': aut},
		proxy: {
		host: 'proxy-1dn.mb',
		port: 6060
		
	 }
	};
	
	geralog(" Emitindo GET REQUEST para " + ender + 'pagamentos/'+ request.body.id_pgto);

        //PRIMEIRO BLOCO AYNC
        /**
         * Consulta o PagTesouro para obter o status atualizado do pagamento
         * e persiste o resultado no banco. Quando o pagamento é concluído,
         * também aciona a integração SINGRA para sincronização.
         *
         * @returns {Promise<void>}
         * @example
         * await sendPost();
         */
        const sendPost = async () => {
	
	const { Pool } = require('pg')								
	const pool = new Pool({
		user: '#',
		host: '#', 
		database: '#',
		schema: '#',
		password: '#',
		port: 1111
	})
	
	try {

		const response2 = await axios.get(ender + 'pagamentos/'+ request.body.id_pgto , options);
		geralog("Resposta PagTesouro recebida!");
		geralog("======= INÍCIO DA RESPOSTA ========");
		console.log(response2.data);
		var resposta = response2.data;    			
		geralog("======= FIM DA RESPOSTA ========");
		geralog("Atualizando informações no Banco de Dados.");  
		//connection.connect();
						
		if (resposta["situacao"]["codigo"] == "CONCLUIDO") {
								
		//POSTGRES
		var query = "UPDATE pagtesouro.tb_pgto  set 	ds_tp_pgto  = $1,	vr_pago  = $2, ds_nomepsp  = $3 ,  cd_transacpsp  = $4,  ds_situacao  = $5,   dt_situacao  = TO_TIMESTAMP($6,'YYYY-MM-DD\"T\"HH24:MI:SSZ')  where id_pgto  = $7";
		var values = [resposta["tipoPagamentoEscolhido"],resposta["valor"],resposta["nomePSP"],resposta["transacaoPSP"],resposta["situacao"]["codigo"],resposta["situacao"]["data"],request.body.id_pgto];				 
			// callback
			//pool.query(query, [request.body.id_pgto](err, res) => {
		pool.query(query, values, (err, res) => {
		if (err) {														
		geralog("Erro no registro! (POSTGRES) " + err.stack);
		} else {
		geralog("Atualização no Banco de Dados - UPDATE STATUS - efetuado com sucesso! (POSTGRES)");	
									
		//COMUNICAÇÃO AO SINGRA 
									
		geralog(" Novo pedido com situação concluída. idPagamento : " + request.body.id_pgto);    				
									
		geralog("Categoria do pedido: " + request.body.cat_servico)
									
		if (request.body.cat_servico == "CCIM") {
										
										 geralog("Montagem de dados para envio ao SINGRA. Os seguintes dados serão enviados:");
										
										var singra = new Object();
										singra.id_pgto = request.body.id_pgto;
										singra.cpf = request.body.cd_cpf;
		
										singra.situacao = resposta["situacao"]["codigo"];
										singra.tp_pgto = resposta["tipoPagamentoEscolhido"];
										singra.dt_pgto = resposta["situacao"]["data"];
										singra.nome_PSP = resposta["nomePSP"];
										//singra.id_transac = resposta["transacaoPSP"];
										singra.id_transac = request.body.id_pgto;
										singra.vr_pago = resposta["valor"];
										
										console.log(singra);
										
										//A LINHA ABAIXO TORNA A CONEXÃO HTTPS INSEGURA!! 
										//process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
										
										geralog("Enviando POST-REQUEST para SINGRA (https://www.api-singra.dabm.mb/pagtesouro/pagamento/");
																								
															
										//NOVO REQUEST - SEM AXIOS
										var options = {
										  hostname: 'www.api-singra.dabm.mb',
										  port: 443,
										  path: '/pagtesouro/pagamento',
										  method: 'POST',
										  headers: {
											   'Content-Type': 'application/json',											   
											   'Authorization': 'Basic ' + new Buffer.from('admin' + ':' + 'pwssingra').toString('base64')											   
											 },
										  cert: fs.readFileSync('recim-chain.pem') 
		
										};
										
										https.globalAgent.options.ca = rootCas + fs.readFileSync('recim-chain.pem') ;
										
										let req = https.request(options, (res) => {
										  //console.log('statusCode:', res.statusCode);
										  //console.log('headers:', res.headers);

										  res.on('data', (d) => {
											
											geralog(d);
											
											if (d == 'sucesso:true') {
											e = {
												'sucesso' : true,
												'erro_cod' : 0,
												'erro_msg' : 'sem erro'
											}	
											} else {																							
											e = JSON.parse(d);
											}
											geralog('Recebida Resposta do SINGRA! Conteúdo:');
											geralog("sucesso: " + e["sucesso"]);
											geralog("erro_cod: " + e["erro_cod"]);
											geralog("erro_Msg: " + e["erro_Msg"]);
											
											//process.stdout.write(d);
											
											if ( (e["sucesso"] == true) || (e["sucesso"] == false && e["erro_Msg"] == 'Pagamento registrado anteriormente')) {
												geralog("SINGRA respondeu com sucesso. Saldo do usuário atualizado");
												
												if (e["sucesso"] == false && e["erro_Msg"] == 'Pagamento registrado anteriormente') {
													response.send(JSON.stringify(["1 fail",e["erro_cod"]+": " + e["erro_Msg"]]));
												} else 
												{
												response.send(JSON.stringify(["1 ok"]));
												}
												geralog("Enviada resposta ao usuário.");  
												
													//POSTGRES
													var query = "UPDATE pagtesouro.tb_pgto SET singra_ok = 1 WHERE id_pgto = $1";
													var values = [request.body.id_pgto] // 
													 
													// callback
													//pool.query(query, [request.body.id_pgto](err, res) => {
													pool.query(query, values, (err, res) => {
													  if (err) {														
														geralog("Erro no registro! " + err.stack);
													  } else {
														geralog("Atualização no Banco de Dados - SINGRA_OK - efetuado com sucesso! (POSTGRES)");	
													  }
													  })
											} else {
												geralog("SINGRA respondeu com erro.");
												response.send(JSON.stringify(["1 fail",e["erro_cod"]+": " + e["erro_Msg"]]));
												geralog("Enviada resposta ao usuário.");  
											}
										  });
										});

										req.on('error', (e) => {
										  geralog("Erro na comunicação com o SINGRA! " + e); 
										  response.send(JSON.stringify(["0","" + e]));
										  geralog("Enviada resposta ao usuário.");
										  
										});

										req.write(JSON.stringify(singra));
										req.end(); 
									
									} else {
										
										response.send(JSON.stringify(["1"]));
										geralog("Enviada resposta ao usuário.");  
										geralog("Atualização finalizada com sucesso.");
										
									}
									
								  }
								  })
								
								
							}  else {
								
								var query = "UPDATE pagtesouro.tb_pgto  set 	ds_tp_pgto  = $1,	vr_pago  = $2, ds_nomepsp  = $3 ,  cd_transacpsp  = $4,  ds_situacao  = $5,   dt_situacao  = TO_TIMESTAMP($6,'YYYY-MM-DD\"T\"HH24:MI:SSZ')  where id_pgto  = $7";
								var values = [resposta["tipoPagamentoEscolhido"],resposta["valor"],resposta["nomePSP"],resposta["transacaoPSP"],resposta["situacao"]["codigo"],resposta["situacao"]["data"],request.body.id_pgto];
								 
								// callback
								//pool.query(query, [request.body.id_pgto](err, res) => {
								pool.query(query, values, (err, res) => {
								  if (err) {														
									geralog("Erro no registro! (POSTGRES) " + err.stack);
								  } else {
									geralog("Atualização no Banco de Dados - UPDATE STATUS - efetuado com sucesso! (POSTGRES)");	
									response.send("1");
									geralog("Enviada resposta ao usuário.");  		
								  }
								  })
								
							}
						
					} catch (error) {
						var erro = new Object();						
						
						console.error(typeof(error.response));
						if (typeof(error.response)!=='undefined'){
							geralog("Acesso: " + cont + " - Resposta do REQUEST com Erro!");
							console.log(error.response.data);
							for (const [key,value] of Object.entries(error.response.data)) {
								geralog("Erro: " + value["codigo"] + " - " + value["descricao"]);	
								erro[value["codigo"]] = value["descricao"];
								}
							erro["situacao"] = { codigo: 'CORRIGIR' };
							geralog("Enviando resposta de correção ao Usuário...");
							console.log(erro);
							response.send("0");
						
						} else {
							erro["situacao"] = { codigo: 'ERRO' };
							console.log(error);
							response.send("0");
						
						}
					}
					//connection.end();
					};	
					
					sendPost();
					
});

var server = https.createServer(options, app).listen(port, function(){
  geralog(`Servidor DGOM GRU ativado. Atento para conexões em localhost:${port}`);
});
