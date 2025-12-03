<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);  
*/

require('conpg11.php');

function base64_url_encode($input)

{
return strtr(base64_encode($input), '+/=', '-_,');
}

      
# session_start inicia a sessão
session_start();

$usuario = addslashes($_POST['usuario']);
$senha = addslashes($_POST ['senha']);
$senha = hash('sha256', $senha);

$info_array = array($usuario, $senha);

// Consultar o banco de dados para verificar se a senha corresponde a uma entrada válida
$query = "SELECT * FROM pagtesouro.tb_login WHERE usuario=$1 and senha=$2 limit 1";
$consulta = pg_query_params($conn,$query,$info_array);

$status = 'inativo';

while ($row = pg_fetch_row($consulta)){
	$senha = $row[2];
	$cd_om = $row[3];
	$status = $row[5];
	$contador = $row[4];
} 

                                                                                                               
if (pg_num_rows($consulta) == 1 AND $status == 'ativo' AND $senha == 'xxxxx'){
	//Print_r($consulta);
	//print $contador.$status.$senha;	
	//sleep(5);
	unset ($_SESSION['usuario']);
	unset ($_SESSION['senha']);
	unset ($_SESSION['cd_om']);
	header('location:login.php?reiniciar&usuario='.base64_url_encode($usuario));
	exit;
}

if (pg_num_rows($consulta) == 1 AND $contador <= 2 AND $status == 'ativo'){
	
   $info_array2 = array($usuario);	
   $query2 = "UPDATE pagtesouro.tb_login set contador = 0 where usuario = $1";
   $consulta2 = pg_query_params($conn,$query2,$info_array2);
	
   $_SESSION['usuario'] = $usuario; 
   $_SESSION['senha'] = $senha; 
   $_SESSION['cd_om'] = $cd_om;
   header('location:grid/index.php'); 
   
   } elseif (pg_num_rows($consulta) == 1 AND $status == 'inativo'){
		unset ($_SESSION['usuario']);
		unset ($_SESSION['senha']);
		unset ($_SESSION['cd_om']);
		header('location:login.php?block');
	   
      
   } else {
   
   $info_array2 = array($usuario);	
   $query2 = "UPDATE pagtesouro.tb_login set contador = contador + 1, status = case when contador >= 2 then 'inativo' else 'ativo' end where usuario = $1";
   $consulta2 = pg_query_params($conn,$query2,$info_array2);
         
   unset ($_SESSION['usuario']);
   unset ($_SESSION['senha']);
   unset ($_SESSION['cd_om']);
   header('location:login.php?invalido');
   }

 
/*        
$info_array3 = array ($usuario);
$query3 = "UPDATE pagtesouro.tb_login SET status = 'inativo' WHERE usuario=$1";
$consulta3 = pg_query_params($conn,$query3,$info_array3);
*/

?>
