<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);  
*/

header ('Content-type: text/html; charset=utf-8');

session_start();
if((!isset ($_SESSION['login']) == true) and (!isset ($_SESSION['senha']) == true)){
  unset($_SESSION['usuario']);
  unset($_SESSION['senha']);
  unset($_SESSION['cd_om']);
  header('location:../login.php');
  }

$login = $_SESSION['usuario'];
$cd_om = $_SESSION['cd_om'];

include('../conpg11.php'); // Conexão com Postgres	
	
	function mask($val, $mask){
	$maskared = '';
	$k = 0;
        for($i = 0; $i<=strlen($mask)-1; $i++){ if($mask[$i] == '#') { if(isset($val[$k])) 
                $maskared .= $val[$k++];
		}else {
	        	if(isset($mask[$i]))
		$maskared .= $mask[$i];
		}
	}    
	return $maskared;
}
	
	if (isset($_POST['update'])){
		
	$linha = json_decode($_POST['dados'],true);
	//print_r($linha);		
		
	$query = pg_query_params($conn, 'UPDATE pagtesouro.tb_pgto SET ds_obs = $1 WHERE id_pgto = $2', array($linha['ds_obs'], $linha['id_pgto']));
		/* $query = "UPDATE pagtesouro.TB_PGTO SET DS_OBS = ? WHERE ID_PGTO = ? ";
		$result = $mysqli->prepare($query);				
		$result->bind_param('ss', $linha['ds_obs'], $linha['id_pgto']); */
		if ($query == false) $res = pg_last_error(); else $res = 1;
		/* $res = $result->execute() or trigger_error($result->error, E_USER_ERROR); */
		
	 echo $res;	
		
	} elseif (isset($_GET['delete'])) {
	
	} elseif (isset($_GET['addrow'])) {
	
	} else  {
		//Print_r($_POST);
		// Initialize pagenum and pagesize
	$pagenum = $_POST['pagenum'];
	$pagesize = $_POST['pagesize'];	
	//$cd_om = $_POST['cd_om'];
	
	
	$start = $pagenum * $pagesize;
     
     
		 if (isset($_POST['sortdatafield']))
		{
			$sortfield = $_POST['sortdatafield'];
			$sortorder = $_POST['sortorder'];
			$fq = $sortfield . ' ' . $sortorder;
		} else {
			$fq = 'dt_situacao desc';
		}
		
		$fq = str_replace( array( '\'', '"',',' , ';', '<', '>',"'", 'SELECT', 'DROP', 'DATABASE' , 'TABLE'), '', $fq);
		//$fq = str_replace( array('"',',' , ';', '<', '>',"'", 'SELECT', 'DROP', 'DATABASE' , 'TABLE'), '', $fq);
		
		/*if ($cd_om == '64200') {

		 
		 
		 $query2= pg_query($conn, "SELECT pgto.id_pgto, svc.ds_servico, pgto.ds_situacao, pgto.dt_situacao, pgto.nome, pgto.cd_cpf, pgto.vr_principal, pgto.cd_referencia, pgto.cat_servico, pgto.ds_tp_pgto, pgto.vr_pago, pgto.ds_obs, pgto.cd_om,
           pgto.cod_rubrica, pgto.nome_rubrica, pgto.motivo, pgto.tributavel, pgto.nomeoc, pgto.vr_bruto_ex_ant, pgto.vr_ex_atu, pgto.natdev, pgto.cod_siapenip, pgto.cod_oc, pgto.cod_om, pgto.dt_competencia, pgto.singra_ok
          FROM pagtesouro.tb_pgto pgto LEFT JOIN pagtesouro.tb_servico SVC ON (svc.id_servico = pgto.id_servico) WHERE pgto.cat_servico = 'PAPEM' AND pgto.natdev LIKE '%ecupera%tivos' order by " . $fq);
		 
		 		 
		 $cd_om = 'PAPEM';
		} elseif ($cd_om != 'PAPEM') {
           $params = array($cd_om);
           $query2 = pg_query_params($conn, "SELECT pgto.id_pgto, svc.ds_servico, pgto.ds_situacao, pgto.dt_situacao, pgto.nome, pgto.cd_cpf, pgto.vr_principal, pgto.cd_referencia, pgto.cat_servico, pgto.ds_tp_pgto, pgto.vr_pago, pgto.ds_obs, pgto.singra_ok 
		   FROM pagtesouro.tb_pgto pgto LEFT JOIN pagtesouro.tb_servico SVC ON (svc.id_servico = pgto.id_servico) WHERE pgto.cd_om = $1 order by " . $fq,$params);
	 

		} else {        	 
        	
        	$query2 =  pg_query($conn, "SELECT pgto.id_pgto, svc.ds_servico, pgto.ds_situacao, pgto.dt_situacao, pgto.nome,
        		pgto.cd_cpf, pgto.vr_principal, pgto.cd_referencia,  pgto.cat_servico, pgto.ds_tp_pgto, pgto.vr_pago, pgto.ds_obs, pgto.cd_om,
		 pgto.cod_rubrica, pgto.nome_rubrica, pgto.motivo, pgto.tributavel, pgto.nomeoc, pgto.vr_bruto_ex_ant, pgto.vr_ex_atu, pgto.natdev, pgto.cod_siapenip, pgto.cod_oc, pgto.cod_om, pgto.dt_competencia, pgto.singra_ok
		 FROM pagtesouro.tb_pgto pgto LEFT JOIN pagtesouro.tb_servico SVC ON (svc.id_servico = pgto.id_servico) WHERE pgto.cat_servico = 'PAPEM' order by " . $fq);
		 

		}
		*/
		switch ($cd_om){
			case '64200':
				$query2= pg_query($conn, "SELECT pgto.id_pgto, svc.ds_servico, pgto.ds_situacao, pgto.dt_situacao, pgto.nome, pgto.cd_cpf, pgto.vr_principal, pgto.cd_referencia, pgto.cat_servico, pgto.ds_tp_pgto, pgto.vr_pago, pgto.ds_obs, pgto.cd_om,
				pgto.cod_rubrica, pgto.nome_rubrica, pgto.motivo, pgto.tributavel, pgto.nomeoc, pgto.vr_bruto_ex_ant, pgto.vr_ex_atu, pgto.natdev, pgto.cod_siapenip, pgto.cod_oc, pgto.cod_om, pgto.dt_competencia, pgto.singra_ok
				FROM pagtesouro.tb_pgto pgto LEFT JOIN pagtesouro.tb_servico SVC ON (svc.id_servico = pgto.id_servico) WHERE pgto.cat_servico = 'PAPEM' AND pgto.natdev LIKE '%ecupera%tivos' order by " . $fq);
				$cd_om = 'PAPEM';
				break;
			
			case 'PAPEM': 
				$query2 =  pg_query($conn, "SELECT pgto.id_pgto, svc.ds_servico, pgto.ds_situacao, pgto.dt_situacao, pgto.nome,
        		pgto.cd_cpf, pgto.vr_principal, pgto.cd_referencia,  pgto.cat_servico, pgto.ds_tp_pgto, pgto.vr_pago, pgto.ds_obs, pgto.cd_om,
				pgto.cod_rubrica, pgto.nome_rubrica, pgto.motivo, pgto.tributavel, pgto.nomeoc, pgto.vr_bruto_ex_ant, pgto.vr_ex_atu, pgto.natdev, pgto.cod_siapenip, pgto.cod_oc, pgto.cod_om, pgto.dt_competencia, pgto.singra_ok
				FROM pagtesouro.tb_pgto pgto LEFT JOIN pagtesouro.tb_servico SVC ON (svc.id_servico = pgto.id_servico) WHERE pgto.cat_servico = 'PAPEM' order by " . $fq);
				break;
			
			case 'IMH': 
				$query2 =  pg_query($conn, "SELECT pgto.id_pgto, svc.ds_servico, pgto.ds_situacao, pgto.dt_situacao, pgto.nome,
				pgto.cd_cpf, pgto.vr_principal, pgto.cd_referencia,  pgto.cat_servico, pgto.ds_tp_pgto, pgto.vr_pago, pgto.ds_obs, pgto.cd_om,
				pgto.cod_rubrica, pgto.nome_rubrica, pgto.motivo, pgto.tributavel, pgto.nomeoc, pgto.vr_bruto_ex_ant, pgto.vr_ex_atu, pgto.natdev, pgto.cod_siapenip, pgto.cod_oc, pgto.cod_om, pgto.dt_competencia, pgto.singra_ok
				FROM pagtesouro.tb_pgto pgto LEFT JOIN pagtesouro.tb_servico SVC ON (svc.id_servico = pgto.id_servico) WHERE pgto.cat_servico = 'IMH' order by " . $fq);
				//$cd_om = 'IMH';
				break;
				
			default:
				$params = array($cd_om);
				$query2 = pg_query_params($conn, "SELECT pgto.id_pgto, svc.ds_servico, pgto.ds_situacao, pgto.dt_situacao, pgto.nome, pgto.cd_cpf, pgto.vr_principal, pgto.cd_referencia, pgto.cat_servico, pgto.ds_tp_pgto, pgto.vr_pago, pgto.ds_obs, pgto.singra_ok 
				FROM pagtesouro.tb_pgto pgto LEFT JOIN pagtesouro.tb_servico SVC ON (svc.id_servico = pgto.id_servico) WHERE pgto.cd_om = $1 order by " . $fq,$params); 
				break;
		}

				 
		$key = "xxxxxxxx";
		$iv_size = '16';
		$iv = '0000000000000000';
		
		
		if ($cd_om != "PAPEM") {	
			
			
			$prioriza = array();
			
		
			while (($row = pg_fetch_assoc($query2)) != false) {
				
			
				
				$item = array();
				$item['id_pgto'] = $row['id_pgto'];
				$item['ds_servico'] = $row['ds_servico'];
				$item['ds_situacao'] = $row['ds_situacao'];
				$item['dt_situacao'] = $row['dt_situacao'];
				$item['nome'] = openssl_decrypt($row['nome'],'aes128',$key,0,$iv);
				$item['cd_cpf'] = openssl_decrypt($row['cd_cpf'],'aes128',$key,0,$iv);
				$item['vr_principal'] = $row['vr_principal'];
				$item['cd_referencia'] = $row['cd_referencia'];
				$item['cat_servico'] = $row['cat_servico'];
				$item['ds_tp_pgto'] = $row['ds_tp_pgto'];
				$item['vr_pago'] = $row['vr_pago'];
				$item['ds_obs'] = $row['ds_obs'];
				$item['singra_ok'] = $row['singra_ok'];
			    $prioriza[] = $item;
			}
        
		} else {
			
			$prioriza = array();
			
			while (($row = pg_fetch_assoc($query2)) != false) {
				
				$item = array();
				$item['id_pgto'] = $row['id_pgto'];
				$item['ds_servico'] = $row['ds_servico'];
				$item['ds_situacao'] = $row['ds_situacao'];
				$item['dt_situacao'] = $row['dt_situacao'];
				
				// Precisa criptografar os dados			
				//$item['nome'] = $row['nome'];
				//$item['cd_cpf'] = $row['cd_cpf'];
				$item['nome'] = openssl_decrypt($row['nome'],'aes128',$key,0,$iv);
				$item['cd_cpf'] = openssl_decrypt($row['cd_cpf'],'aes128',$key,0,$iv);
				
				$item['vr_principal'] = $row['vr_principal'];
				$item['cd_referencia'] = $row['cd_referencia'];
				$item['cat_servico'] = $row['cat_servico'];
				$item['ds_tp_pgto'] = $row['ds_tp_pgto'];
				$item['vr_pago'] = $row['vr_pago'];
				$item['ds_obs'] = $row['ds_obs'];
				$item['cd_om'] = $row['cd_om'];
				$item['cod_rubrica'] = $row['cod_rubrica'];
				$item['nome_rubrica'] = $row['nome_rubrica'];
				$item['motivo'] = $row['motivo'];
				$item['tributavel'] = $row['tributavel'];
				$item['nome_oc'] = $row['nomeoc'];
				$item['vr_bruto_ex_ant'] = $row['vr_bruto_ex_ant'];
				$item['vr_ex_atu'] = $row['vr_ex_atu'];
				$item['natdev'] = $row['natdev'];
				$item['cod_siapenip'] = $row['cod_siapenip'];
				$item['cod_oc'] = $row['cod_oc'];
				$item['cod_om'] = $row['cod_om'];
				$item['competencia'] = $row['dt_competencia'];
				$item['singra_ok'] = $row['singra_ok'];
				$prioriza[] = $item;
			  
			}
			
			
		}		
			
		if ($prioriza == null) {$prioriza = array();}
		$data[] = array(
		   'TotalRows' => pg_num_rows($query2),
		   'Rows' => $prioriza
		);
		echo json_encode($data);
		
	}	
	
?>
