<?php

require_once(dirname(__FILE__) . '/facebook.php');
require_once(dirname(__FILE__) . '/core.php');

session_start();

$pdo = pdo();

if(!$pdo)
  redirect('../');

if(isset($_GET['error']))
  redirect('../');

$fb = new Facebook(array('appId' => '', 'secret' => ''));

$facebookId = $fb->getUser();

if(hasRegistered($pdo, $facebookId)) {
  $userId = userId($pdo, $facebookId);

} else {
  $userId = regist($pdo, $facebookId);

  initUser($pdo, $userId);
}

$_SESSION['userId'] = $userId;

redirect('../');

function hasRegistered($pdo, $facebookId) {
  $stmt = $pdo->prepare('select coalesce(count(*), 0) from user where facebookId = ?');
  $stmt->execute(array($facebookId));
  $row = $stmt->fetch();
  return $row[0] != 0;
}
function userId($pdo, $facebookId) {
  $stmt = $pdo->prepare('select userId from user where facebookId = ?');
  $stmt->execute(array($facebookId));
  $row = $stmt->fetch();
  return $row[0];
}
function regist($pdo, $facebookId) {
  $stmt = $pdo->prepare('insert into user(facebookId, createTime) values(?, ?)');
  $stmt->execute(array($facebookId, getDatef()));
  return $pdo->lastInsertId();
}
