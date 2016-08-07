<?php

require_once(dirname(__FILE__) . '/php/htmltemplate.php');
require_once(dirname(__FILE__) . '/php/core.php');

session_start();
session_regenerate_id();

$pdo = pdo();

$ua = $_SERVER['HTTP_USER_AGENT'];
if(strpos($ua, 'iPhone') !== false || strpos($ua, 'iPod') !== false || strpos($ua, 'Android') !== false)
  $ht['sp'] = '※スマートフォンには<br>対応しておりません。';

if(!$pdo || !isset($_SESSION['userId'])) {
  htmltemplate::t_include(dirname(__FILE__) . '/html/signin.html', $ht);
  exit;
}

$userId = $_SESSION['userId'];

updateUser($pdo, $userId);

$categories = categories($pdo, $userId)->fetchAll(PDO::FETCH_ASSOC);
foreach($categories as &$category) {
  $category['category'] = htmlspecialchars($category['category'], ENT_QUOTES);
  if(!isEmpty($pdo, $category['categoryId']))
    $category['more'] = "<button class='more'>More</button>";
}

$ht['sessionId'] = session_id();
$ht['categories'] = $categories;

/* $categoryW = isset($_GET['categoryW']) ? $_GET['categoryW'] : 160; */
/* $todoW = isset($_GET['todoW']) ? $_GET['todoW'] : 360; */
$categoryW = 160;
$todoW = 360;

$ht['containerW'] = $categoryW + $todoW + 156;
$ht['categoryW'] = $categoryW + 8;
$ht['todoW'] = $todoW + 106;
$ht['todoNameW'] = $todoW;
$ht['noteW'] = $todoW + 88;
$ht['sbCategoryW'] = $categoryW + 20;
$ht['hue'] = 6 * 30;

htmltemplate::t_include(dirname(__FILE__) . '/html/index.html', $ht);

function updateUser($pdo, $userId) {
  $stmt = $pdo->prepare('update user set updateTime = ?, sessionId = ? where userId = ?');
  $stmt->execute(array(getDatef(), session_id(), $userId));
}
function categories($pdo, $userId) {
  $stmt = $pdo->prepare('select categoryId, category from category where userId = ? and hasDeleted = 0 order by rankId desc');
  $stmt->execute(array($userId));
  return $stmt;
}
function isEmpty($pdo, $categoryId) {
  $stmt = $pdo->prepare('select coalesce(count(*), 0) from todo where categoryId = ? and hasDeleted = 0');
  $stmt->execute(array($categoryId));
  $row = $stmt->fetch();
  return $row[0] == 0;
}