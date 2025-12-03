<html>
 </head>
 <meta http-equiv="content-type" content="text/html; charset=UTF-8">
 <title>Login - Gestão PagTesouro</title>
 
<link rel="icon" type="image/logomarca_mb.png" href="style/images/logomarca_mb.png">
<link rel="stylesheet" type="text/css" href="style/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/reiniciar_senha.css">
<link rel="stylesheet" type="text/css" href="style/view.css/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="style/view.css/bootstrap/css/bootstrap-theme.min.css">
<link rel="stylesheet" type="text/css" href="style/view.css/glyphicons/css/glyphicons.min.css">
<link rel="stylesheet" type="text/css" href="style/kendo.ui.2014.2.716/styles/web/kendo.blueopal.css">
<link rel="stylesheet" type="text/css" href="style/kendo.ui.2014.2.716/styles/web/kendo.common.min.css">
<script type="text/javascript" src="style/kendo.ui.2014.2.716/js/jquery.min.js"></script>
<script type="text/javascript" src="style/kendo.ui.2014.2.716/js/kendo.all.min.js"></script>
<script type="text/javascript" src="style/kendo.ui.2014.2.716/js/cultures/kendo.culture.pt-BR.min.js"></script>
<script type="text/javascript" src="style/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="style/view.css/bootstrap/js/jquery.mask.min.js"></script>
<script type="text/javascript" src="style/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/login.js"></script>

<script>
function myFunction() {
  alert("Contate o Departamento do Plano Diretor da DGOM. E-mail: dgom.senhasiplad@marinha.mil.br. Tel: 2104-6217/6221");
}
</script>


<style>
	.bg{
		background-image: url('/pagtesouro/style/images/bg_login.jpg');
		background-repeat: no-repeat;
		background-attachment: fixed;
		background-size: cover;
}

</style>

<body class="bg">
	<div class="vh-20 d-flex justify-content-center align-items-center">
		<div class="container justify-content-center">
			<h3 class="text-center text-white pt-5"></h3>
				<div class="row d-flex justify-content-center">
					<div class="col-12 col-md-9 col-lg-5">
						<div class="border border-3 border-primary"></div> <!-- Borda azul superior-->
							<div class="card bg-white shadow-lg">
								<div class="card-body p-5">
								<center><img src="style/images/logomarca_mb.png" alt="Logomarca MB" title="Logomarca MB" style="width:95px; height:95px"></center>
           
								<form method="post" class="mb-2 mt-md-4" action="check_login.php">
									<h2 class="fw-bold mb-2 text-uppercase text-align:center" style="text-align:center">GESTÃO PAGTESOURO</h2>

									<p class=" mb-5" id="p1" style="text-align:center; margin-top:20px;">Por favor, acesse com seu usuário e senha!</p>

									<div class="mb-2" id="divUser">
										<label class="form-label">Usuário</label>
										<input type="text" name="usuario" id="usuarioId" placeholder="Digite o nome do usuário" class="form-control" auto-complete="off"><br>
									</div>

									<div class="mb-1" id="divPassword">
										<label for="password" class="form-label">Senha</label>
										<input type="password" name="senha" id="passwordId" placeholder="**********" class="form-control" auto-complete="off"><br>
									</div>
								

									<p class="small" id="p2" style="text-align:center; font-size:15px"onclick="myFunction()">Esqueceu a senha?</a></p>
									<div class="d-grid">
									<button class="btn btn-outline-dark" id="btnEntrar" type="submit">Entrar</button>
								</form>


									<?php if(isset($_GET['invalido'])){ ?>
										<p>
											<div class="btn btn-danger" role="alert" style="text-align:center">Senha e/ou usuário inválidos.</div>
										</p>
									<?php } ?>

									<?php if(isset($_GET['block'])){ ?>
										<p>
											<div class="btn btn-danger" role="alert" style="text-align:center">Usuário bloqueado por 3 tentativas inválidas. Contate o Administrador do Sistema.</div>
										</p>
									<?php } ?>

									<?php if(isset($_GET['senha_atualizada'])){ ?>
										<p>
											<div class="btn btn-success" role="alert" style="text-align:center">Senha atualizada com sucesso.</div>
										</p>
									<?php } ?>

									<?php if(isset($_GET['reiniciar'])){ 
										function base64_url_decode($input){
											return base64_decode(strtr($input, '-_,', '+/='));
										}
									?>
									<script>
										disableInput();
									</script>

									<form method="post" id='atu_form' name='atu_form' action="update_login.php">					
										<input type="hidden" name="usuario2" id='usuario2'  placeholder="Digite o usuário" class="form-control" auto-complete="off" value="<?php echo base64_url_decode($_GET['usuario']); ?>">
										<hr>	
										<p><b>Requisitos para nova senha:</b></p>
											<ul>
												<li>Deve ter no mínimo 12 caracteres</li>
												<li>Deve incluir pelo menos uma letra maiúscula e um minúscula</li>
												<li>Deve incluir pelo menos um número</li>
												<li>Deve incluir pelo menos um caractere especial (ex: @, #, $, !)</li>
											</ul>
										<div class="mb-2" style="margin-top:35px">			
											<label name='senha2' class="form-label">Digite uma nova senha:</label><br>
											<input type="password" name="senha2" id='senha2' placeholder="Digite a nova senha" class="form-control piscar piscar-borda" auto-complete="off"><br>
										</div>
													
										<?php if(isset($_GET['formato_senha'])){ ?>
											<p>
												<div class="btn btn-warning" role="alert" style="text-align:center">Obedeça ao padrão da DCTIM.<br> Mínimo de 12 dígitos.  Pelo menos uma letra maiúscula, número e caracter especial.</div>
											</p>
										<?php } ?>
		
										<center><input class="btn btn-primary"value="Atualizar" type="submit"></center>
									</form>
									
								<?php } ?> 								

              				</div>
			            </form>
					</div>
				</div>
			</div>
		</div>
	</div>

	</body>
	</head>
</html>
