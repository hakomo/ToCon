<?php

require_once(dirname(__FILE__) . '/core.php');

$pdo = init();

if(!$pdo)
  signout();

$queue = $_POST['queue'];

foreach($queue as $val)
  $res[] = $val['action']($pdo, $val);

echo json_encode($res);

function editTodo($pdo, $val) {
  $todoId = $val['todoId'];
  $todo = $val['todo'];

  $stmt = $pdo->prepare('update todo set todo = ? where todoId = ?');
  $stmt->execute(array($todo, $todoId));
  return null;
}

function editCategory($pdo, $val) {
  $categoryId = $val['categoryId'];
  $category = $val['category'];

  $stmt = $pdo->prepare('update category set category = ? where categoryId = ?');
  $stmt->execute(array($category, $categoryId));
  return null;
}

function search($pdo, $val) {
  $userId = $_SESSION['userId'];
  $categoryId = $val['categoryId'];
  $order = $val['order'];
  $search = $val['search'];

  if(false) {
  } else if($categoryId == -1 && $order == 'category') {
    $todoes = array();
    foreach(categories($pdo, $userId) as $category) {
      $categoryId = $category[0];

      $todoes = array_merge($todoes, search_($pdo, $categoryId, $order, $search));
    }

  } else if($categoryId == -1) {
    $sql = '(';
    foreach(categories($pdo, $userId) as $category)
      $sql .= 'categoryId = ' . $category[0] . ' or ';
    $sql = substr($sql, 0, -4);
    $sql .= ')';

    if($search == '') {
      $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked from todo where ' . $sql . ' and hasDeleted = 0 and hasChecked = 0 order by updateTime asc');
      $stmt->execute();

    } else {
      $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked from todo where ' . $sql . ' and hasDeleted = 0 and hasChecked = 0 and (todo like ? or note like ?) order by updateTime asc');
      $search = '%' . $search . '%';
      $stmt->execute(array($search, $search));
    }
    $ary1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($search == '') {
      $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked from todo where ' . $sql . ' and hasDeleted = 0 and hasChecked = 1 order by updateTime asc');
      $stmt->execute();

    } else {
      $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked from todo where ' . $sql . ' and hasDeleted = 0 and hasChecked = 1 and (todo like ? or note like ?) order by updateTime asc');
      $search = '%' . $search . '%';
      $stmt->execute(array($search, $search));
    }
    $ary2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $todoes = array_merge($ary1, $ary2);

  } else {
    $todoes = search_($pdo, $categoryId, $order, $search);
  }

  foreach($todoes as &$todo) {
    $todo['todo'] = htmlspecialchars($todo['todo'], ENT_QUOTES);
    $todo['note'] = htmlspecialchars($todo['note'], ENT_QUOTES);
    $todo['updateTime'] = strtotime($todo['updateTime']);
  }

  return array('action' => 'search', 'todoes' => $todoes);
}
function categories($pdo, $userId) {
  $stmt = $pdo->prepare('select categoryId from category where userId = ? and hasDeleted = 0 order by rankId desc');
  $stmt->execute(array($userId));
  return $stmt;
}
function search_($pdo, $categoryId, $order, $search) {
  if(false) {
  } else if($order == 'category' && $search == '') {
    $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked from todo where categoryId = ? and hasDeleted = 0 order by rankId desc');
    $stmt->execute(array($categoryId));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);

  } else if($order == 'category') {
    $stmt = $pdo->prepare("select todoId, todo, note, updateTime, hasChecked from todo where categoryId = ? and hasDeleted = 0 and (todo like ? or note like ?) order by rankId desc");
    $search = '%' . $search . '%';
    $stmt->execute(array($categoryId, $search, $search));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);

  } else if($search == '') {
    $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked from todo where categoryId = ? and hasDeleted = 0 and hasChecked = 0 order by updateTime asc');
    $stmt->execute(array($categoryId));
    $ary1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked from todo where categoryId = ? and hasDeleted = 0 and hasChecked = 1 order by updateTime asc');
    $stmt->execute(array($categoryId));
    $ary2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_merge($ary1, $ary2);

  } else {
    $stmt = $pdo->prepare("select todoId, todo, note, updateTime, hasChecked from todo where categoryId = ? and hasDeleted = 0 and hasChecked = 0 and (todo like ? or note like ?) order by updateTime asc");
    $search = '%' . $search . '%';
    $stmt->execute(array($categoryId, $search, $search));
    $ary1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("select todoId, todo, note, updateTime, hasChecked from todo where categoryId = ? and hasDeleted = 0 and hasChecked = 1 and (todo like ? or note like ?) order by updateTime asc");
    $search = '%' . $search . '%';
    $stmt->execute(array($categoryId, $search, $search));
    $ary2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_merge($ary1, $ary2);
  }
}

function upCategory($pdo, $val) {
  $userId = $_SESSION['userId'];
  $categoryId = $val['categoryId'];
  $nextCategoryId = $val['nextCategoryId'];

  list($rankId, $nextRankId) =
    rankIdsCategory($pdo, array($categoryId, $nextCategoryId));

  $stmt = $pdo->prepare('update category set rankId = rankId - 1 where userId = ? and hasDeleted = 0 and rankId > ? and rankId <= ?');
  $stmt->execute(array($userId, $rankId, $nextRankId));

  $stmt = $pdo->prepare('update category set rankId = ? where categoryId = ?');
  $stmt->execute(array($nextRankId, $categoryId));
  return null;
}
function downCategory($pdo, $val) {
  $userId = $_SESSION['userId'];
  $categoryId = $val['categoryId'];
  $prevCategoryId = $val['prevCategoryId'];

  list($rankId, $prevRankId) =
    rankIdsCategory($pdo, array($categoryId, $prevCategoryId));

  $stmt = $pdo->prepare('update category set rankId = rankId + 1 where userId = ? and hasDeleted = 0 and rankId >= ? and rankId < ?');
  $stmt->execute(array($userId, $prevRankId, $rankId));

  $stmt = $pdo->prepare('update category set rankId = ? where categoryId = ?');
  $stmt->execute(array($prevRankId, $categoryId));
  return null;
}
function rankIdsCategory($pdo, $categoryIds) {
  $stmt = $pdo->prepare('select rankId from category where categoryId = ?');
  foreach($categoryIds as $categoryId) {
    $stmt->execute(array($categoryId));
    $row = $stmt->fetch();
    $rankIds[] = $row[0];
  }
  return $rankIds;
}

function upTodo($pdo, $val) {
  $categoryId = $val['categoryId'];
  $todoId = $val['todoId'];
  $nextTodoId = $val['nextTodoId'];

  list($rankId, $nextRankId) =
    rankIdsTodo($pdo, array($todoId, $nextTodoId));

  $stmt = $pdo->prepare('update todo set rankId = rankId - 1 where categoryId = ? and hasDeleted = 0 and rankId > ? and rankId <= ?');
  $stmt->execute(array($categoryId, $rankId, $nextRankId));

  $stmt = $pdo->prepare('update todo set rankId = ? where todoId = ?');
  $stmt->execute(array($nextRankId, $todoId));
  return null;
}
function downTodo($pdo, $val) {
  $categoryId = $val['categoryId'];
  $todoId = $val['todoId'];
  $prevTodoId = $val['prevTodoId'];

  list($rankId, $prevRankId) =
    rankIdsTodo($pdo, array($todoId, $prevTodoId));

  $stmt = $pdo->prepare('update todo set rankId = rankId + 1 where categoryId = ? and hasDeleted = 0 and rankId >= ? and rankId < ?');
  $stmt->execute(array($categoryId, $prevRankId, $rankId));

  $stmt = $pdo->prepare('update todo set rankId = ? where todoId = ?');
  $stmt->execute(array($prevRankId, $todoId));
  return null;
}
function rankIdsTodo($pdo, $todoIds) {
  $stmt = $pdo->prepare('select rankId from todo where todoId = ?');
  foreach($todoIds as $todoId) {
    $stmt->execute(array($todoId));
    $row = $stmt->fetch();
    $rankIds[] = $row[0];
  }
  return $rankIds;
}

function toggleNote($pdo, $val) {
  $todoId = $val['todoId'];

  $stmt = $pdo->prepare('update todo set hasOpened = if(hasOpened = 0, 1, 0) where todoId = ?');
  $stmt->execute(array($todoId));
  return null;
}

function dotodo($pdo, $val) {
  $todoId = $val['todoId'];

  $stmt = $pdo->prepare('insert into history(todoId, updateTime) values(?, ?)');
  $stmt->execute(array($todoId, getDatef()));

  $stmt = $pdo->prepare('update todo set updateTime = ? where todoId = ?');
  $stmt->execute(array(getDatef(), $todoId));
  return null;
}

function deleteCategory($pdo, $val) {
  $categoryId = $val['categoryId'];

  $stmt = $pdo->prepare('update category set hasDeleted = 1 where categoryId = ?');
  $stmt->execute(array($categoryId));
  return null;
}

function deleteTodo($pdo, $val) {
  $todoId = $val['todoId'];

  $stmt = $pdo->prepare('update todo set hasDeleted = 1 where todoId = ?');
  $stmt->execute(array($todoId));
  return null;
}

/* function closeNotes($pdo, $val) { */
/*   $userId = $_SESSION['userId']; */

/*   foreach(categoryIds($pdo, $userId) as $row) { */
/*     $categoryId = $row[0]; */

/*     $stmt = $pdo->prepare('update todo set hasOpened = 0 where categoryId = ? and hasOpened = 1'); */
/*     $stmt->execute(array($categoryId)); */
/*   } */
/*   return null; */
/* } */
/* function categoryIds($pdo, $userId) { */
/*   $stmt = $pdo->prepare('select categoryId from category where userId = ? and hasDeleted = 0'); */
/*   $stmt->execute(array($userId)); */
/*   return $stmt; */
/* } */

function complete($pdo, $val) {
  $todoId = $val['todoId'];

  $stmt = $pdo->prepare('update todo set hasChecked = if(hasChecked = 0, 1, 0) where todoId = ?');
  $stmt->execute(array($todoId));
  return null;
}

function updateNote($pdo, $val) {
  $todoId = $val['todoId'];
  $note = $val['note'];

  $stmt = $pdo->prepare('update todo set note = ? where todoId = ?');
  $stmt->execute(array($note, $todoId));
  return null;
}

function changeCategory($pdo, $val) {
  $categoryId = $val['categoryId'];
  $todoId = $val['todoId'];

  $rankId = maxRankId($pdo, $categoryId) + 1;

  $stmt = $pdo->prepare('update todo set categoryId = ?, rankId = ? where todoId = ?');
  $stmt->execute(array($categoryId, $rankId, $todoId));
  return null;
}
function maxRankId($pdo, $categoryId) {
  $stmt = $pdo->prepare('select coalesce(max(rankId), 0) from todo where categoryId = ?');
  $stmt->execute(array($categoryId));
  $row = $stmt->fetch();
  return $row[0];
}

function addTodo($pdo, $val) {
  $categoryId = $val['categoryId'];
  $todo = $val['todo'];

  $stmt = $pdo->prepare('insert into todo(categoryId, rankId, todo, createTime, updateTime) select ?, coalesce(max(rankId), 0) + 1, ?, ?, ? from todo where categoryId = ?');
  $stmt->execute(array($categoryId, $todo, getDatef(), getDatef(), $categoryId));
  $todoId = $pdo->lastInsertId();

  $todo = htmlspecialchars($todo, ENT_QUOTES);
  return array('action' => 'addTodo', 'todoId' => $todoId, 'todo' => $todo);
}

function addCategory($pdo, $val) {
  $userId = $_SESSION['userId'];
  $category = $val['category'];

  $categoryId = addCategory_($pdo, $userId, $category);

  $category = htmlspecialchars($category, ENT_QUOTES);
  return array('action' => 'addCategory',
               'categoryId' => $categoryId, 'category' => $category);
}

function load($pdo, $val) {
  $categoryId = $val['categoryId'];
  $todoId = $val['todoId'];

  $todoes = todoes($pdo, $categoryId, $todoId)->fetchAll(PDO::FETCH_ASSOC);

  foreach($todoes as &$todo) {
    $todo['todo'] = htmlspecialchars($todo['todo'], ENT_QUOTES);
    $todo['note'] = htmlspecialchars($todo['note'], ENT_QUOTES);
    $todo['updateTime'] = strtotime($todo['updateTime']);
  }

  $isLast = isLast($pdo, $categoryId, $todoes[count($todoes) - 1]['todoId']);

  return array('action' => 'load', 'todoes' => $todoes, 'isLast' => $isLast);
}
function todoes($pdo, $categoryId, $todoId) {
  if($todoId == -1) {
    $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked, hasOpened from todo where categoryId = ? and hasDeleted = 0 order by rankId desc limit 20');
    $stmt->execute(array($categoryId));

  } else {
    $rankId = rankId($pdo, $todoId);

    $stmt = $pdo->prepare('select todoId, todo, note, updateTime, hasChecked, hasOpened from todo where categoryId = ? and hasDeleted = 0 and rankId < ? order by rankId desc limit 20');
    $stmt->execute(array($categoryId, $rankId));
  }
  return $stmt;
}
function rankId($pdo, $todoId) {
  $stmt = $pdo->prepare('select rankId from todo where todoId = ?');
  $stmt->execute(array($todoId));
  $row = $stmt->fetch();
  return $row[0];
}
function isLast($pdo, $categoryId, $todoId) {
  $rankId = rankId($pdo, $todoId);

  $stmt = $pdo->prepare('select min(rankId) from todo where categoryId = ? and hasDeleted = 0');
  $stmt->execute(array($categoryId));
  $row = $stmt->fetch();
  return $row[0] == $rankId;
}