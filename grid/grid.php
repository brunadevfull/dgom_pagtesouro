<?php
/* esse bloco de código em php verifica se existe a sessão, pois o usuário pode
 simplesmente não fazer o login e digitar na barra de endereço do seu navegador
o caminho para a página principal do site (sistema), burlando assim a obrigação de
fazer um login, com isso se ele não estiver feito o login não será criado a session,
então ao verificar que a session não existe a página redireciona o mesmo
 para a index.php.*/
 

function base64_url_decode($input)

        {
			return base64_decode(strtr($input, '-_,', '+/='));
        }

      
      $cd_agreg_meta = $_POST['cd_agreg_meta'];


?>


<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>SUBSÍDIOS PA2022 - <?php echo $cd_agreg_meta ?></title>
	
    <link rel="stylesheet" type="text/css" href="../style/kendo.ui.2014.2.716/styles/web/kendo.blueopal.css">
    <link rel="stylesheet" type="text/css" href="../style/kendo.ui.2014.2.716/styles/web/kendo.common.min.css">
    <link rel="stylesheet" type="text/css" href="../style/view.css/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style/view.css/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" type="text/css" href="../style/view.css/glyphicons/css/glyphicons.min.css">
    <script type="text/javascript" src="../style/kendo.ui.2014.2.716/js/jquery.min.js"></script>
    <script type="text/javascript" src="../style/kendo.ui.2014.2.716/js/kendo.all.min.js"></script>
    <script type="text/javascript" src="../style/kendo.ui.2014.2.716/js/cultures/kendo.culture.pt-BR.min.js"></script>
    <script type="text/javascript" src="../style/view.css/bootstrap/js/bootstrap.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1 maximum-scale=1 minimum-scale=1" />
    <link rel="stylesheet" href="jqwidgets/styles/jqx.base.css" type="text/css" />
    <link rel="stylesheet" href="jqwidgets/styles/jqx.energyblue.css" type="text/css" />	
    <script type="text/javascript" src="scripts/jquery-1.11.1.min.js"></script>  
    <script type="text/javascript" src="jqwidgets/jqxcore.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxbuttons.js"></script>
	<script type="text/javascript" src="jqwidgets/jqxdata.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxscrollbar.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxmenu.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxcheckbox.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxlistbox.js"></script>
	<script type="text/javascript" src="jqwidgets/jqxdropdownlist.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxgrid.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxgrid.pager.js"></script>
    <script type="text/javascript" src="jqwidgets/jqxgrid.selection.js"></script>	
    <script type="text/javascript" src="jqwidgets/jqxgrid.sort.js"></script>		
    <script type="text/javascript" src="jqwidgets/jqxdata.js"></script>	
    <script type="text/javascript" src="jqwidgets/jqxdata.export.js"></script>	
    <script type="text/javascript" src="jqwidgets/jqxgrid.export.js"></script>	
	<script type="text/javascript" src="jqwidgets/jqxgrid.edit.js"></script>
	<script type="text/javascript" src="jqwidgets/jqxnumberinput.js"></script>	
	<script type="text/javascript" src="../style/view.css/bootstrap/js/jquery.mask.min.js"></script>	
	
    <script type="text/javascript">
	
	$(document).ready(function () {        
	
	var source =  {
			datatype: "json",
            datafields:  [
				{ name: 'id_prioriza', type: 'string'},
				{ name: 'ds_agreg_pa', type: 'string'},
				{ name: 'ds_ods', type: 'string'},
				{ name: 'cd_meta', type: 'string'},
				{ name: 'cd_ai', type: 'string'},
				{ name: 'cd_ao', type: 'string'},
				{ name: 'cd_po', type: 'string'},
				{ name: 'ds_justificativa', type: 'string'},				
				{ name: 'cd_nd', type: 'string'},
				{ name: 'cd_moeda', type: 'string'},
				{ name: 'vr_base', type: 'number'}
				
			],
            cache: false,
	        url: 'subsidio.php',
			type: "POST",
		    root: 'Rows',
			beforeprocessing: function(data)
		    {		    	
				source.totalrecords = data[0].TotalRows;								
		    },
			sort: function()
			{
				$("#jqxgrid").jqxGrid('updatebounddata', 'sort');
				
			 }
		};	
		
		var dataAdapter = new $.jqx.dataAdapter(source, 
        {
			formatData: function (data) {
				$.extend(data, {
					cd_agreg_meta: "<?php echo $cd_agreg_meta ?>" ,							
				}); 				
				return data;
			}
		});
		
		var theme = 'energyblue';
		
		// initialize jqxGrid
		$("#jqxgrid").jqxGrid(
		{
			width: '1050px',
            source: dataAdapter, 
			//altrows: true,
			theme: theme,
			autoheight: true,
			sortable: true,
			virtualmode: false,
			pageable: true,
			editable: false,
			selectionmode: 'singlerow',				
			rendergridrows: function(params)				
			{
				return params.data;
			},
            columns: [
				{ text: 'Identificador', datafield: 'id_prioriza', width: 100},  
				{ text: 'Agregador PA', datafield: 'ds_agreg_pa', width: 150},
				{ text: 'ODS', datafield: 'ds_ods', width: 100},
				{ text: 'Meta', datafield: 'cd_meta', width: 100},
				{ text: 'Ação Interna', datafield: 'cd_ai', width: 120},
				{ text: 'AO', datafield: 'cd_ao', width: 80},
				{ text: 'PO', datafield: 'cd_po', width: 80},
				{ text: 'Justificativa', datafield: 'ds_justificativa', width: 400},
				{ text: 'ND', datafield: 'cd_nd', width: 100},
				{ text: 'Moeda', datafield: 'cd_moeda', width: 100},
				{ text: 'Valor Base (R$)', datafield: 'vr_base', cellsFormat: 'd2'}
                ]
		});
	
		
		
		
		$("#jqxgrid").bind('bindingcomplete', function (event) {
				$("#jqxgrid").jqxGrid('localizestrings', localizationobj);	});
		
					
			$("#excelExport").jqxButton();
			$("#excelExport").click(function () {
			  // console.log( $("#jqxgrid").jqxGrid('getrowdata', $('#jqxgrid').jqxGrid('selectedrowindexes'))["cod_pedido"] );
               $("#jqxgrid").jqxGrid('exportdata', 'xls', 'prioriza');           
            });

	});
    
    
			//tradução
			var localizationobj = {};
            localizationobj.pagergotopagestring = "Ir para página:";
            localizationobj.pagershowrowsstring = "Quantidade de Registros:";
            localizationobj.pagerrangestring = " de ";
            localizationobj.pagernextbuttonstring = "Próxima página";
            localizationobj.pagerpreviousbuttonstring = "Página Anterior";
            localizationobj.sortascendingstring = "Classificar em ordem crescente";
            localizationobj.sortdescendingstring = "Classificar em ordem descrescente";
            localizationobj.sortremovestring = "Remover classificação";
            localizationobj.firstDay = 1;
            localizationobj.percentsymbol = "%";
            localizationobj.currencysymbol = "R$";
            localizationobj.currencysymbolposition = "before";
            localizationobj.decimalseparator = ",";
            localizationobj.thousandsseparator = ".";
            var days = {
                // full day names
                names: ["Domingo", "Segunda-Feira", "Terça-Feira", "Quarta-Feira", "Quinta-Feira", "Sexta-Feira", "Sábado"],
                // abbreviated day names
                namesAbbr: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sab"],
                // shortest day names
                namesShort: ["Do", "Se", "Te", "Qa", "Qi", "Sx", "Sb"]
            };
            localizationobj.days = days;
            var months = {
                // full month names (13 months for lunar calendards -- 13th month should be "" if not lunar)
                names: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro", ""],
                // abbreviated month names
                namesAbbr: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez", ""]
            };
            localizationobj.months = months;

			</script>
</head>

  <body>
    <div class="container-fluid" style="margin-top: 7px">
      <div class="panel panel-primary">
        	
		
		<div class="panel-body">
		  <div class="panel panel-info">
              <div class="panel-body">
			   
					<div id='jqxWidget'>
					<center>
						<div id='jqxgrid'></div>
					</center>
					</div> 

        <div style='margin-top: 20px;'>
		<center>
            <div >
                <input class="btn btn-primary" value="Exportar para Excel" id='excelExport' />
			</div>
			 
			
      </div>
    </div> 
	</div>
	</div>
	</div>
	</div>

  </body>
</html>
