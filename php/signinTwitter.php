<?php

require_once(dirname(__FILE__) . '/UltimateOAuth.php');
require_once(dirname(__FILE__) . '/core.php');

session_start();

$pdo = pdo();

if(!$pdo)
  redirect('../');

if(!isset($_SESSION['uo']) ||
   !isset($_GET['oauth_verifier']) || !is_string($_GET['oauth_verifier']))
  redirect('../');

$uo = $_SESSION['uo'];

$res = $uo->post('oauth/access_token',
                 array('oauth_verifier' => $_GET['oauth_verifier']));

if(isset($res->errors))
  redirect('../');

$twitterId = $res->user_id;

if(hasRegistered($pdo, $twitterId)) {
  $userId = userId($pdo, $twitterId);

} else {
  $userId = regist($pdo, $twitterId);

  initUser($pdo, $userId);
}

$_SESSION['userId'] = $userId;

redirect('../');

function hasRegistered($pdo, $twitterId) {
  $stmt = $pdo->prepare('select coalesce(count(*), 0) from user where twitterId = ?');
  $stmt->execute(array($twitterId));
  $row = $stmt->fetch();
  return $row[0] != 0;
}
function userId($pdo, $twitterId) {
  $stmt = $pdo->prepare('select userId from user where twitterId = ?');
  $stmt->execute(array($twitterId));
  $row = $stmt->fetch();
  return $row[0];
}
function regist($pdo, $twitterId) {
  $stmt = $pdo->prepare('insert into user(twitterId, createTime) values(?, ?)');
  $stmt->execute(array($twitterId, getDatef()));
  return $pdo->lastInsertId();
}