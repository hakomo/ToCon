<?php

require_once(dirname(__FILE__) . "/php/core.php");

session_start();

unset($_SESSION['userId']);

redirect('index.php');