<?php
/*
 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);  
*/
 $texto = '';
 if (isset($_GET['pad']) == true) {      
	$pad = $_GET['pad'];
	$texto = "<p class='guidelines' id='guide_2'><small><font color='red'><b>Deve ser informado o CPF da pessoa a ser identificada</b></font></small></p>";
 }
	 


?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>PagTesouro</title>

<link rel="icon" type="style/image/logomarca_mb.png" href="style/images/logomarca_mb.png">
<link rel="stylesheet" type="text/css" href="css\view.css" media="all">
<link rel="stylesheet" type="text/css" href="ui\jquery-ui.min.css" media="all">
<link rel="stylesheet" type="text/css" href="css\MonthPicker.css" media="all">
<link rel="stylesheet" type="text/css" href="css\toastify.min.css" media="all">

<script type="text/javascript" src="js\view.js"></script>
<script type="text/javascript" src="js\jquery-3.5.1.js"></script>
<script type="text/javascript" src="ui\jquery-ui.min.js"></script>
<script type="text/javascript" src="js\jquery.mask.min.js"></script>
<script type="text/javascript" src="js\axios.min.js"></script>
<script type="text/javascript" src="js\toastify.js"></script>
<script type="text/javascript" src="js\MonthPicker.js"></script>


</head>



<script type="text/javascript">
      <!--
	 <!--
  
     function mostra_campos() {


     	if (document.formPagTesouro.maiscampos.checked) {
     	document.getElementById("li_8").setAttribute('style', "display: block");
     	document.getElementById("li_9").setAttribute('style', "display: block");
     	document.getElementById("li_10").setAttribute('style', "display: block");
     	document.getElementById("li_11").setAttribute('style', "display: block");
     	document.getElementById("li_12").setAttribute('style', "display: block");
     	
     	} else{
		document.getElementById("li_8").setAttribute('style', "display: none");
		document.getElementById("li_9").setAttribute('style', "display: none");
		document.getElementById("li_10").setAttribute('style', "display: none");
		document.getElementById("li_11").setAttribute('style', "display: none");
		document.getElementById("li_12").setAttribute('style', "display: none");

     	}

     	}
		
	 function atu_cat() {
		
		document.getElementById("campos").setAttribute('style', "display: block");
		document.formPagTesouro.codigoServico.value = "";
		document.formPagTesouro.vencimento.value = "";
		document.formPagTesouro.competencia.value = "";
		document.formPagTesouro.valorPrincipal.value = "";
		document.formPagTesouro.nomeContribuinte.value = "";
		document.formPagTesouro.cnpjCpf.value = "";
		document.formPagTesouro.valorPrincipal.readOnly = false;	
		i = 1;
		combo = document.getElementById("codigoServico");
		
		//limpando combo
		var length = combo.options.length;
				for (j = length-1; j >= 0; j--) {
				  combo.options[j] = null;
				}
				
		var opt = document.createElement("option");
				opt.value = "";
				opt.text  = "";
				combo.add(opt, combo.options[0]);
				
		lista_servicos.forEach( 
		 function(item) {
			 
				
			 
			 if (item['CAT_SERVICO'].includes(document.formPagTesouro.cat.value)) {
				var opt = document.createElement("option");
				opt.value = item['CD_SERVICO'] + ";" + item['CD_OM']+ ";" + item['CAT_SERVICO']+ ";" +item['VL_CPF']+ ";"+ item['VL_CNPJ']+ ";"+ item['ID_SERVICO'] ;
				opt.text  = item['DS_SERVICO'];
				combo.add(opt, combo.options[i]);
				i = i + 1;
			} 			 
		 }
		);	
		
		if (document.formPagTesouro.cat.value == "CCIM") {			
			combo.selectedIndex = 1;
			combo.readOnly = true;	
			//alert(combo.value);
			atu_venc();
		} else {
			combo.readOnly = false;	
			
		}
   /*
		if (document.formPagTesouro.cat.value == "CESUO") {			
			combo.selectedIndex = 1;
			combo.readOnly = true;	
			//alert(combo.value);
			atu_venc();
		} else {
			combo.readOnly = false;	
			
		}*/

		
	 
	 }

	 function testarPopUp() {
            // Tenta abrir uma nova janela
            const novaJanela = window.open('https://pagtesouro.dgom.mb/pagtesouro/', '_blank');

            // Verifica se a nova janela foi bloqueada
            if (!novaJanela || novaJanela.closed || typeof novaJanela.closed == 'undefined') {
                alert("É necessário permitir que o navegador abra janelas/abas para que este formulário funcione.");
            } else {
                // Fecha a janela aberta, já que é só um teste
                novaJanela.close();
            }
        }
		testarPopUp();

 </script>



<body id="main_body" >


<script type="text/javascript">

<?php

# Conexão 
require('conpg11.php');

$query = pg_query($conn, "SELECT * FROM pagtesouro.tb_servico where ambiente = 'PRD'");
# $query = pg_query($conn, "SELECT * FROM pagtesouro.tb_servico where ambiente = 'HMG'");

//postgres
$lista_servicos2 = array();
while (($row = pg_fetch_array($query)) != false) {
	$item = array();
	$item['CD_SERVICO'] = $row['cd_servico'];
	$item['CD_OM'] = $row['cd_om'];
	$item['DS_SERVICO'] = $row['ds_servico'];
	$item['CAT_SERVICO'] = $row['cat_servico'];
	//$item['VL_CPF'] = $row['VL_CPF'];
	$item['VL_CPF'] = number_format( $row['vl_cpf'] ,2,",",".");
	$item['VL_CNPJ'] = $row['vl_cnpj'];
	$item['ID_SERVICO'] = $row['id_servico'];
	$lista_servicos[] = $item;
}
?>

var lista_servicos = <?php echo json_encode($lista_servicos) ?>;
//var lista_servicos2 = <?php echo json_encode($lista_servicos2) ?>;


</script>
	<img id="top" src="top.png" style="width: 800px" alt="">
	
	<div id="form_container" style="width: 800px">
		<h1> <a> Integração PagTesouro </a> </h1>
<div><p><center><a href="https://pagtesouro.dgom.mb/pagtesouro/login.php" target=_blank>Página de Acompanhamento</a></center></p></div>
		
		<form id="formPagTesouro" name="formPagTesouro"  class="appnitro"  method="post" action="" >
		
		<div class="form_description">
		<center>
			<h2>Integração PagTesouro</h2>
			<p></p>
			<!-- <h3 style="color:red; text-align: center;">Prezados usuários,<br>Informamos que, devido à necessidade de manutenção essencial, o PAGTESOURO estará indisponível no dia 19 de outubro, das 7h às 23h.</h3> -->
		</center>
		</div>	

		<div class="form_description">
			<center>
				<h3>		
					<input type="radio" name="cat" id="ccim" value="CCIM" onchange="atu_cat()"> Aquisição de Fardamento Pré-Indenizável
					<input type="radio" name="cat" id="cccpm" value="CCCPM" onchange="atu_cat()">CCCPM	
					<input type="radio" name="cat" id="ht" value="HT" onchange="atu_cat()">Hotéis de Trânsito	
					<input type="radio" name="cat" id="sedime" value="SEDIME" onchange="atu_cat()">SeDiMe	
					<input type="radio" name="cat" id="sim" value="SIM" onchange="atu_cat()">SIM	
					<br><input type="radio" name="cat" id="svcadm" value="SVCADM" onchange="atu_cat()">Serviços Administrativos
					<input type="radio" name="cat" id="ressarc" value="RESSARC" onchange="atu_cat()">Ressarcimento de Despesas
					<input type="radio" name="cat" id="cesuo" value="CESUO" onchange="atu_cat()">Cessão de Uso
					<input type="radio" name="cat" id="svcadm" value="IMH" onchange="atu_cat()">Indenizações Hospitalares
				</h3>
			</center>
		</div>

		<div id="campos" style="display:none">
			
			<ul>
				<li id="li_3" >
				<label class="description" for="element_3" >Selecione um Serviço </label>
			
				<!--<select class="form-select form-select-sm" aria-label="Small select example" id="codigoServico" name="codigoServico" onchange="atu_venc()">
					<option value="" selected="selected"></option>
				</select><br>-->

			<div>
			
			<select class="element select medium" id="codigoServico" name="codigoServico" onchange="atu_venc()"> 
					<option value="" selected="selected"></option>					
			</select>

			


			</div><p class="guidelines" id="guide_3"><small>Selecione o serviço para o qual deseja efetuar um Pagamento</small></p> 
			</li>

			<li id="li_1" >
			<label class="description" for="element_1">Vencimento</label>
			<span>
				<input id="vencimento" name="vencimento" class="element text" size="10" maxlength="10" value="" type="text" /> 
			</span>
			
			<p class="guidelines" id="guide_1"><small>Vencimento da GRU a ser gerada</small></p> 
			</li>		
			
			
			<li id="li_2" >
			<label class="description" for="competencia">Competência </label>
			<div>
				<input id="competencia" name="competencia" class="element text" type="text" size="6" maxlength="6" value="" readonly /> 
			
			</div>
			
			</li>		
			
			
			<li id="li_5" >
			<label class="description" for="nomeContribuinte">Nome do Contribuinte </label>
			<div>
				<input id="nomeContribuinte" name="nomeContribuinte" class="element text medium" type="text" maxlength="255" value=""/> 
			</div> 
			</li>		<li id="li_6" >
			<label class="description" for="cnpjCpf">CPF</label>
			<div>
				<input id="cnpjCpf" name="cnpjCpf" class="element text medium" type="text" maxlength="255" value="" onchange="atu_cnpjCpf()"/> 
			</div> 
			<?php  echo $texto ?>
			</li>	


			<li id="li_8" style="display: none" >
			<label class="description" for="valorDescontos">Descontos: </label>
			<div>
				<input id="valorDescontos" name="valorDescontos" class="element text medium" type="text" /> 			
			</div> 
			</li>

			<li id="li_9" style="display: none" >
			<label class="description" for="valorOutrasDeducoes">Outras Deduções: </label>
			<div>
				<input id="valorOutrasDeducoes" name="valorOutrasDeducoes" class="element text medium" type="text" /> 			
			</div> 
			</li>

			<li id="li_10" style="display: none" >
			<label class="description" for="valorMulta">Multa: </label>
			<div>
				<input id="valorMulta" name="valorMulta" class="element text medium" type="text" /> 			
			</div> 
			</li>

			<li id="li_11" style="display: none" >
			<label class="description" for="valorJuros">Juros: </label>
			<div>
				<input id="valorJuros" name="valorJuros" class="element text medium" type="text" /> 			
			</div> 
			</li>

			<li id="li_12" style="display: none" >
			<label class="description" for="valorOutrosAcrescimos">Outros Acréscimos: </label>
			<div>
				<input id="valorOutrosAcrescimos" name="valorOutrosAcrescimos" class="element text medium" type="text" /> 			
			</div> 
			</li>


				<li id="li_7" >
			<label class="description" for="valorPrincipal"> <b> Valor Principal: <b> </label>
			<div>
				<input id="valorPrincipal" name="valorPrincipal" class="element text medium" type="text" /> 
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" id="maiscampos" name="maiscampos" onchange="mostra_campos()"> Campos Complementares
			</div> 
			</li>	


				
			<li class="buttons">			   
			<input id="saveForm" class="button_text" type="submit" name="submit" value="Conectar PagTesouro"/> 
			</li>
			
			</ul>
		</div>	
		</form>	
		
	</div>
	<img id="bottom" src="bottom.png" style="width: 800px"  alt="">
	
	<!-- <iframe class="iframe-epag" name="resp" id="resp" scrolling="no" src=""></iframe> -->
	
	</body>
	
<script type="text/javascript">

Date.prototype.addDays = function(days) {
    var date = new Date(this.valueOf());
    date.setDate(date.getDate() + days);
    return date;
}

function atu_venc() {	

var today = new Date();
var dd = String(today.getDate()).padStart(2, '0');
var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
var yyyy = today.getFullYear();

var dd2 = String(today.addDays(20).getDate()).padStart(2, '0');
var mm2 = String(today.addDays(20).getMonth() + 1).padStart(2, '0');
var yyyy2 = today.addDays(20).getFullYear();


if (mm !== mm2) {
		document.formPagTesouro.vencimento.value = new Date(yyyy, mm, 0).getDate()+'/'+mm+'/'+yyyy;		
		
	} else {
		document.formPagTesouro.vencimento.value = dd2+'/'+mm+'/'+yyyy;		
	}

if (document.formPagTesouro.cat.value == "CCCPM") 
{
	document.formPagTesouro.vencimento.value = new Date(yyyy, mm, 0).getDate()+'/'+mm+'/'+yyyy;		
	document.formPagTesouro.vencimento.readOnly = false;
}

if (document.formPagTesouro.cat.value == "SEDIME") {
	document.formPagTesouro.vencimento.value = dd+'/'+mm+'/'+yyyy;	
} 

document.formPagTesouro.competencia.value =  mm + '' +   yyyy;

atu_cnpjCpf();
	
}  

function atu_cnpjCpf()  {
	
	
	var vl = document.formPagTesouro.codigoServico.value.split(';')
	document.formPagTesouro.valorPrincipal.readOnly = false;
	document.formPagTesouro.valorPrincipal.value = "";
	//cpf digitado		
	//console.log(vl);
	if (document.formPagTesouro.cnpjCpf.value.length == 11 && vl[3] != "0,00") {
		document.formPagTesouro.valorPrincipal.value = vl[3];	
		$('#valorPrincipal').mask("#.##0,00", {reverse: true});
		if (document.formPagTesouro.valorPrincipal.value != "0,00") document.formPagTesouro.valorPrincipal.readOnly = true;
	} 
	
	if (document.formPagTesouro.cnpjCpf.value.length == 14 && vl[4] != "0") {
		document.formPagTesouro.valorPrincipal.value = vl[4];	
		$('#valorPrincipal').mask("#.##0,00", {reverse: true});
		if (document.formPagTesouro.valorPrincipal.value != "0") document.formPagTesouro.valorPrincipal.readOnly = true;
	} 
	
}

// Restricts input for the set of matched elements to the given inputFilter function.
(function($) {
  $.fn.inputFilter = function(inputFilter) {
    return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
      if (inputFilter(this.value)) {
        this.oldValue = this.value;
        this.oldSelectionStart = this.selectionStart;
        this.oldSelectionEnd = this.selectionEnd;
      } else if (this.hasOwnProperty("oldValue")) {
        this.value = this.oldValue;
        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
      } else {
        this.value = "";
      }
    });
  };
}(jQuery));
	

$('document').ready(function() {
	
	var  pad = '<?php if (isset($pad) == true) { echo $pad; } else { echo ''; } ?>'; 
	if (pad == 'sim') {
			document.getElementById('sim').checked = true;
			atu_cat();
	}
	if (pad == 'bnic') {
			document.getElementById('svcadm').checked = true;
			atu_cat();
	}
	
	

$('#competencia').MonthPicker({ MonthFormat: 'mmyy', Button: '<button type="button">...</button>', StartYear: 2021, ShowIcon: true });
 


$('#valorPrincipal').mask("#.##0,00", {reverse: true});
$('#valorDescontos').mask("#.##0,00", {reverse: true});
$('#valorOutrasDeducoes').mask("#.##0,00", {reverse: true});
$('#valorMulta').mask("#.##0,00", {reverse: true});
$('#valorJuros').mask("#.##0,00", {reverse: true});
$('#valorOutrosAcrescimos').mask("#.##0,00", {reverse: true});

$("#cnpjCpf").inputFilter(function(value) {
    return /^\d*$/.test(value);    // Allow digits only, using a RegExp
  });

});


function handleSubmit(event) {
  event.preventDefault();
  $('#valorPrincipal').unmask();
  $('#valorDescontos').unmask();
  $('#valorOutrasDeducoes').unmask();
  $('#valorMulta').unmask();
  $('#valorJuros').unmask();
  $('#valorOutrosAcrescimos').unmask();

  $('#valorPrincipal').mask("#0.00", {reverse: true});
  $('#valorDescontos').mask("#0.00", {reverse: true});
  $('#valorOutrasDeducoes').mask("#0.00", {reverse: true});
  $('#valorMulta').mask("#0.00", {reverse: true});
  $('#valorJuros').mask("#0.00", {reverse: true});
  $('#valorOutrosAcrescimos').mask("#0.00", {reverse: true});



  const data = new FormData(event.target);
    
  $('#valorPrincipal').mask("#.##0,00", {reverse: true});
  $('#valorDescontos').mask("#.##0,00", {reverse: true});
  $('#valorOutrasDeducoes').mask("#.##0,00", {reverse: true});
  $('#valorMulta').mask("#.##0,00", {reverse: true});
  $('#valorJuros').mask("#.##0,00", {reverse: true});
  $('#valorOutrosAcrescimos').mask("#.##0,00", {reverse: true});

  //const value = data.get('nomeUG');
  
  //pegando todos os dados do formulário
  
  
  const value = Object.fromEntries(data.entries());
  
  //acrescentando todos os demais 
  var campos = value.codigoServico.split(';')
  value.nomeUG = campos[1];
  value.codigoServico = campos[0];
  value.cat_servico = campos[2];
  value.id_servico = campos[5];
  value.modoNavegacao = "2";
  value.tema = "tema-light";
  
  function copyToClipboard(text) {
		window.prompt("Copie para a Área de Transferência: Ctrl+C, Enter", text);
	}	
    
  function escapeRegExp(string) {
	return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
  }
  function replaceAll(str, match, replacement){
    return str.replace(new RegExp(escapeRegExp(match), 'g'), ()=>replacement);
  }
  value.vencimento = replaceAll(value.vencimento,"/",""); 
  value.nomeContribuinte = value.nomeContribuinte.match(/[\p{L}-]+/ug).join(' ');
  
  //ẃŕýśǵḱĺźńḿ
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ẃ","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ŕ","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ý","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ś","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ǵ","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ḱ","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ĺ","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ź","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ń","");
  value.nomeContribuinte = replaceAll(value.nomeContribuinte,"ḿ","");
  
   
  if (value.nomeUG != "" && value.codigoServico != "" && value.competencia != "" && value.nomeContribuinte != "" && value.cnpjCpf != "" && value.valorPrincipal != "")  {
  
  //fazendo post request  
  //axios.post('http://10.209.1.97:3000/handle', value).then((response) => {
  document.formPagTesouro.submit.disabled = true;
//  axios.post('https://desenvolvimento.dgom.mb:3000/handle', value, {  withCredentials: false  }).then((response) => {
	axios.post('https://pagtesouro.dgom.mb:3000/handle', value, {  withCredentials: false  }).then((response) => {
		
				
	
				
				if (response.data["situacao"]["codigo"] == "CRIADO") {
					 
					Toastify({
						text: "Pagamento iniciado! Seguir as instruções da janela do PagTesouro.",
						duration: 3000,
						close: true,
						gravity: "top", // `top` or `bottom`
						position: "left", // `left`, `center` or `right`
						backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",					
  
					}).showToast();
					document.formPagTesouro.submit.disabled = false; ;
					
					console.log("URL do PagTesouro: " + response.data.proximaUrl);
					//safa-onça, copiando para o clipboard a URL de resposta, caso seja ug 88000 - PORTAL
					if (value.nomeUG == '88000' || value.nomeUG == '87000' || value.nomeUG == '82000' || value.nomeUG == '81000' || value.id_servico == '118') copyToClipboard(response.data.proximaUrl);
					
					window.open (response.data.proximaUrl , "PagTesouro", "height=700,width=800,scrollbars=0");
					
					
				} else if (response.data["situacao"]["codigo"] == "CORRIGIR") {
					delete response.data.situacao;
					for (const [key,value] of Object.entries(response.data)) {
						//console.log("Erro: " + value["codigo"] + " - " + value["descricao"]);	
						Toastify({
						text: "Erro: " + key + " - " + value,
						duration: 3000,
						close: true,
						gravity: "top", // `top` or `bottom`
						position: "left", // `left`, `center` or `right`
						backgroundColor: "linear-gradient(to right, #b00061, #fa003a)",					
  
					}).showToast();	
					document.formPagTesouro.submit.disabled = false ;				
					}
					
					//alert ("Resposta PagTesouro: " + JSON.stringify(response.data));
				} else {
					Toastify({
						text: "Erro! Contactar a Assessoria do Plano Diretor da DGOM",
						duration: 3000,
						close: true,
						gravity: "top", // `top` or `bottom`
						position: "left", // `left`, `center` or `right`
						backgroundColor: "linear-gradient(to right, #b00061, #bf022e)",					
  
					}).showToast();	
					document.formPagTesouro.submit.disabled = false ;
				}
				
				
				}, (error) => {
				console.log(error);
				});
					} else {
	  
					Toastify({
						text: "Por favor, preencha todos os campos!",
						duration: 3000,
						close: true,
						gravity: "top", // `top` or `bottom`
						position: "left", // `left`, `center` or `right`
						backgroundColor: "linear-gradient(to right, #b00061, #bf022e)",					
  
					}).showToast();	
	  				}
  
  
}

const form = document.querySelector('form');
form.addEventListener('submit', handleSubmit);

</script>
</html>
