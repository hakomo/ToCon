<?php

require_once(dirname(__FILE__) . '/php/facebook.php');
require_once(dirname(__FILE__) . '/php/core.php');

session_start();

unset($_SESSION['userId']);

$fb = new Facebook(array('appId' => '', 'secret' => ''));

redirect($fb->getLoginUrl(array('redirect_uri' => 'http://tocon.info/php/signinFacebook.php')));
