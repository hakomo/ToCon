<?php

require_once(dirname(__FILE__) . '/php/UltimateOAuth.php');
require_once(dirname(__FILE__) . '/php/core.php');

session_start();

unset($_SESSION['userId']);

$_SESSION['uo'] =
  new UltimateOAuth('', '');

$uo = $_SESSION['uo'];

$res = $uo->post('oauth/request_token');

if(isset($res->errors))
  redirect('./');

redirect($uo->getAuthenticateURL());
