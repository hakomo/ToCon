<?php

require_once(dirname(__FILE__) . '/php/core.php');

session_start();

$pdo = pdo();

$stmt = $pdo->prepare('select todo, updateTime from todo');
$stmt->execute();

foreach($stmt->fetchAll() as $todo)
  echo $todo[0] . ' ' . $todo[1] . '<br>';
