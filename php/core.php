<?php

function pdo() {
  try {
    return new PDO('', '', '');
  } catch(PDOException $e) {
    return null;
  }
}
function redirect($uri) {
  header('location: ' . $uri);
  exit;
}
function signout() {
  unset($_SESSION['userId']);
  exit;
}
function init() {
  session_start();

  $pdo = pdo();

  if(!$pdo || !isset($_SESSION['userId']))
    return null;

  $userId = $_SESSION['userId'];

  $sessionId = sessionId($pdo, $userId);

  return $sessionId == $_POST['sessionId'] ? $pdo : null;
}
function sessionId($pdo, $userId) {
  $stmt = $pdo->prepare('select sessionId from user where userId = ?');
  $stmt->execute(array($userId));
  $row = $stmt->fetch();
  return $row[0];
}
function getDatef() {
  $datetime = new DateTime();
  return $datetime->format('Y-m-d H:i:s');
}
function escape($val) {
  return is_array($val) ? array_map('escape', $val) :
    htmlspecialchars($val, ENT_QUOTES);
}
function addCategory_($pdo, $userId, $category) {
  $stmt = $pdo->prepare('insert into category(userId, category) values(?, ?)');
  $stmt->execute(array($userId, $category));
  $categoryId = $pdo->lastInsertId();
  $stmt = $pdo->prepare('update category set rankId = ? where categoryId = ?');
  $stmt->execute(array($categoryId, $categoryId));
  return $categoryId;
}
function initUser($pdo, $userId) {
  addCategory_($pdo, $userId, '欲しいもの');
  addCategory_($pdo, $userId, 'アイデア');
  $categoryId = addCategory_($pdo, $userId, '続けたいこと');
  $stmt = $pdo->prepare('insert into todo(categoryId, rankId, todo, createTime, updateTime) select ?, coalesce(max(rankId), 0) + 1, ?, ?, ? from todo where categoryId = ?');
  foreach(array('ダイエットをする', '本を読む', '英語の勉強をする', '運動をする') as $todo) {
    $stmt->execute(array($categoryId, $todo, getDatef(), getDatef(), $categoryId));
  }
  $categoryId = addCategory_($pdo, $userId, 'チュートリアル');
  $stmt = $pdo->prepare('insert into todo(categoryId, rankId, todo, createTime, updateTime) select ?, coalesce(max(rankId), 0) + 1, ?, ?, ? from todo where categoryId = ?');
  foreach(array('歯車をクリックして色々しよう', 'ToDoの歯車をリストへドラッグしてリストを変更しよう', '歯車をドラッグして並び替えよう', 'ToDoリスト名をクリックして切り換えよう', '左上の入力からToDoリストを追加しよう', 'ToDo名をクリックしてノートを編集しよう', 'ToDoを完了したら時間を長押ししよう', 'ToDoに取り組んだら時間をクリックしよう', '上の入力からToDoを追加しよう') as $todo) {
    $datetime = new DateTime();
    $datetime->modify('-' . rand(1, 20) . ' day');
    $stmt->execute(array($categoryId, $todo, getDatef(), $datetime->format('Y-m-d H:i:s'), $categoryId));
  }
}
