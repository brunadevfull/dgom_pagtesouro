<?php
session_start();
session_destroy();
// Redirecionar para tela de login
header('Location: login.php');
?>
