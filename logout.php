<?php
require_once 'classes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: /index.php');
exit;
?>