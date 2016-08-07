
var action = {
  state: 0,
  index: 0,
  queue: []
};

$(function() {

  $(document).on('click', '.edit-todo', function() {
    var name = prompt('ToDo名を入力してください',
                      $(this).parents('.clickdown').prev().text());

    if(name == null || name == '') return;

    name = name.substring(0, 90);

    $(this).parents('.clickdown').prev().text(name);

    if($(this).parents('#search').length == 1)
      $("#todo .todoId[value='" + $(this).parents('.todo').children('input').val() + "']").next().children('.todo-name').text(name);

    action.queue.push({
      'action': 'editTodo',
      'todoId': $(this).parents('.todo').children('input').val(),
      'todo': name
    });
  });

  $(document).on('click', '.edit-category', function() {
    var name = prompt('ToDoリスト名を入力してください',
                      $(this).parents('.clickdown').prev().text());

    if(name == null || name == '') return;

    name = name.substring(0, 90);

    $(this).parents('.clickdown').prev().text(name);

    updateSelect();

    action.queue.push({
      'action': 'editCategory',
      'categoryId': $(this).parents('.category').children('input').val(),
      'category': name
    });
  });

  $('.sh-form').on('submit', function() {
    $('.sb-search').val($(this).children('input').val());
    if($('#todo.selected').length == 1) {
      $('.sb-category').val($('.category.selected').children('input').val());
      $('.sb-order').val('category');
      $('.selected').removeClass('selected');
      $('#search').addClass('selected');
    }
    $('.sb-form').submit();
    return false;
  });
  $('.sh-order').on('click', function() {
    if($('#todo.selected').length == 1) {
      $('.sb-search').val('');
      $('.sb-category').val($('.category.selected').children('input').val());
      $('.sb-order').val('time');
      $('.selected').removeClass('selected');
      $('#search').addClass('selected');
    }
    $('.sb-form').submit();
  });
  $('.sb-form').on('submit', function() {
    $('#search .wrap').empty();

    action.queue.push({
      'action': 'search',
      'categoryId': $('.sb-category').val(),
      'order': $('.sb-order').val(),
      'search': $('.sb-search').val()
    });

    block();
    return false;
  });

  $(document).on('click', '.delete-category', function() {
    if($('.category').length == 1) {
      alert('最後の1つは削除できません。');
      return;
    }

    if(!confirm('削除しますか？')) return;

    var categoryId = $(this).parents('.category').children('input').val();
    $(".categoryId[value='" + categoryId + "']").parent().remove();

    if($('.selected').length == 1)
      $('.category-name:first').click();

    updateSelect();

    action.queue.push({
      'action': 'deleteCategory',
      'categoryId': categoryId
    });
  });

  $(document).on('click', '.delete-todo', function() {
    if($(this).parents('#search').length == 1)
      $("#todo .todoId[value='" + $(this).parents('.todo').children('input').val() + "']").parents('.todo').remove();

    $(this).parents('.todo').remove();

    action.queue.push({
      'action': 'deleteTodo',
      'todoId': $(this).parents('.todo').children('.todoId').val()
    });
  });

  var $menu = null;
  $(document).on('click', '.clickdown > a', function(e) {
    if(false) {
    } else if($menu == null) {
      $menu = $(this).next();
      $menu.show();
      $menu.css({ 'left': e.clientX - ($(this).parents('#header').length == 1 ? 168 : 80), 'top': e.clientY + 1 });
    } else if($(this).next().css('display') != 'none') {
      $menu.hide();
      $menu = null;
    } else {
      $menu.hide();
      $menu = $(this).next();
      $menu.show();
      $menu.css({ 'left': e.clientX - ($(this).parents('#header').length == 1 ? 168 : 80), 'top': e.clientY + 1 });
    }
    return false;
  });
  $(document).on('click', function() {
    if($menu == null) return;
    $menu.hide();
    $menu = null;
  });

  $(document).on('click', '.category-name', function() {
    $('.selected').removeClass('selected');
    $('#todo').addClass('selected');
    $(this).parent().addClass('selected');
    $("#todo .categoryId[value='" + $(this).prev().val() + "']").parent().
      addClass('selected');

    if($('.todoes.selected .more').length != 0 &&
       $('.todoes.selected .wrap').children().length == 0)
      $('.todoes.selected .more').click();
  });

  $(document).on('click', '.more', function() {
    action.queue.push({
      'action': 'load',
      'categoryId': $('.todoes.selected .categoryId').val(),
      'todoId': $('.todoes.selected .todo:last .todoId').val() || -1
    });

    block();
  });

  $('#add-todo').on('submit', function() {
    if($(this).children('input').val() === '')
      return false;

    var todo = $(this).children('input').val();
    $(this).children('input').val('');

    action.queue.push({
      'action': 'addTodo',
      'categoryId': $('.category.selected .categoryId').val(),
      'todo': todo
    });

    block();
    return false;
  });

  $('#add-category').on('submit', function() {
    if($(this).children('input').val() === '')
      return false;

    var category = $(this).children('input').val();
    $(this).children('input').val('');

    action.queue.push({
      'action': 'addCategory',
      'category': category
    });

    block();
    return false;
  });

  $(document).on('click', '.todo-name', function() {
    $(this).parent().next().toggle().children().focus();

    if($(this).parents('#search').length == 1) return;

    action.queue.push({
      'action': 'toggleNote',
      'todoId': $(this).parent().prev().val()
    });
  });

  var clickable = true;
  $(document).on('mousedown', '.complete', function() {
    var $this = $(this);

    var sto = setTimeout(function() {
      $this.html($this.children().length == 1 ? elapsedTime($this.prev().val() / 60) : "<i class='icon-ok'></i>");

      if($this.parents('#search').length == 1)
        $("#todo .todoId[value='" + $this.parent().prev().val() + "']").next().children('.do').html($this.html());

      action.queue.push({
        'action': 'complete',
        'todoId': $this.parent().prev().val()
      });

      clickable = false;
      $this.on('mouseout', function() {
        clickable = true;
        $(this).off('mouseout');
      });
    }, 500);

    $(this).on('mouseout mouseup', function() {
      clearTimeout(sto);
      $(this).off('mouseout mouseup');
    });
  });

  $(document).on('click', '.do', function() {
    if(!clickable) {
      clickable = true;
      return;
    }

    $(this).text('0 分').prev().val(Math.floor(new Date().getTime() / 1000));

    if($(this).parents('#search').length == 1)
      $("#todo .todoId[value='" + $(this).parent().prev().val() + "']").next().children('.do').text('0 分').prev().val(Math.floor(new Date().getTime() / 1000));

    action.queue.push({
      'action': 'dotodo',
      'todoId': $(this).parent().prev().val()
    });
  });

  $(document).on('change', '.note', function() {
    if($(this).parents('#search').length == 1)
      $("#todo .todoId[value='" + $(this).parent().prev().prev().val() + "']").next().next().children().val($(this).val());

    action.queue.push({
      'action': 'updateNote',
      'todoId': $(this).parent().prev().prev().val(),
      'note': $(this).val()
    });
  });

  $('.category-name:first').click();

  able();

  $(window).unload(function() {
    if(action.state > 0 || action.queue.length == 0) return;

    action.state = 1;
    action.index = action.queue.length;
    ajax();
  });

  setInterval(function() {
    if(action.state > 0 || action.queue.length == 0) return;

    action.state = 1;
    action.index = action.queue.length;
    ajax();
  }, 2000);

  setInterval(function() {
    $('.do:not(:has(i))').each(function() {
      $(this).text(elapsedTime($(this).prev().val() / 60));
    });
  }, 60000);

  // hover();
});

function updateSelect() {
  var s = "<option value='-1'>ALL</option>";
  $('#category .categoryId').each(function() {
    s+= "<option value='" + $(this).val() + "'>" + $(this).next().text() + "</option>";
  });
  $('.sb-category').html(s);
}

function block() {
  $.blockUI({
    message: null,
    overlayCSS: {
      opacity: 0
    }
  });
  if(action.state == 0) {
    action.state = 3;
    ajax();
  } else {
    action.state = 2;
  }
}

function elapsedTime(t) {
  t = Math.max(0, new Date().getTime() / 60000 - t);
  return t < 60 ? Math.floor(t) + ' 分' :
    (t / 60 < 24 ? Math.floor(t / 60) + ' 時間' : Math.floor(t / 1440) + ' 日');
}

function ajax() {
  $.ajax({
    type: 'post',
    url: 'php/action.php',
    data: {
      'sessionId': $('#sessionId').val(),
      'queue': action.queue
    },
    error: function(res) {
      if(action.state != 1)
        action.queue.pop();
      if(action.state == 3)
        $.unblockUI();
      action.state = 0;
    },
    success: function(res) {
      var i, j;
      if(res == '')
        location.reload();
      res = JSON.parse(res);
      for(i = 0; i < res.length; ++i) {
        var val = res[i];
        if(val == null) continue;
        if(false) {
        } else if(val['action'] == 'search') {
          var todoes = val['todoes'];
          for(j = 0; j < todoes.length; ++j) {
            var todo = todoes[j];
            $('#search .wrap').append("<div class='todo'><input class='todoId' type='hidden' value='" + todo['todoId'] + "'><div><input type='hidden' value='" + todo['updateTime'] + "'><button class='do complete'>" + (todo['hasChecked'] == 0 ? elapsedTime(todo['updateTime'] / 60) : "<i class='icon-ok'></i>") + "</button><button class='todo-name'>" + todo['todo'] + "</button><div class='clickdown'><a class='detail' href='#'><i class='icon-cog'></i></a><ul><li><a class='edit-todo' href='#'>編集</a></li><li><a class='delete-todo' href='#'>削除</a></li></ul></div></div><div style='display: none;'><textarea class='note' spellcheck='false' placeholder='ノート' maxlength='700'>" + todo['note'] + "</textarea></div>");
          }

          // hover();
        } else if(val['action'] == 'load') {
          if(val['isLast'])
            $('.todoes.selected .more').remove();

          var todoes = val['todoes'];
          for(j = 0; j < todoes.length; ++j) {
            var todo = todoes[j];
            $('.todoes.selected .wrap').append("<div class='todo'><input class='todoId' type='hidden' value='" + todo['todoId'] + "'><div><input type='hidden' value='" + todo['updateTime'] + "'><button class='do complete'>" + (todo['hasChecked'] == 0 ? elapsedTime(todo['updateTime'] / 60) : "<i class='icon-ok'></i>") + "</button><button class='todo-name'>" + todo['todo'] + "</button><div class='clickdown'><a class='detail' href='#'><i class='icon-cog'></i></a><ul><li><a class='edit-todo' href='#'>編集</a></li><li><a class='delete-todo' href='#'>削除</a></li></ul></div></div><div" + (todo['hasOpened'] == 0 ? " style='display: none;'" : '') + "><textarea class='note' spellcheck='false' placeholder='ノート' maxlength='700'>" + todo['note'] + "</textarea></div>");
          }

          // hover();
        } else if(val['action'] == 'addTodo') {
          $('.todoes.selected .wrap').prepend("<div class='todo'><input class='todoId' type='hidden' value='" + val['todoId'] + "'><div><input type='hidden' value='" + Math.floor(new Date().getTime() / 1000) + "'><button class='do complete'>0 分</button><button class='todo-name'>" + val['todo'] + "</button><div class='clickdown'><a class='detail' href='#'><i class='icon-cog'></i></a><ul><li><a class='edit-todo' href='#'>編集</a></li><li><a class='delete-todo' href='#'>削除</a></li></ul></div></div><div style='display: none;'><textarea class='note' spellcheck='false' placeholder='ノート' maxlength='700'></textarea></div>");

          // hover();
        } else if(val['action'] == 'addCategory') {
          $('#category .wrap').prepend("<div class='category'><input class='categoryId' type='hidden' value='" + val['categoryId'] + "'><button class='category-name'>" + val['category'] + "</button><div class='clickdown'><a class='detail' href='#'><i class='icon-cog'></i></a><ul><li><a class='edit-category' href='#'>編集</a></li><li><a class='delete-category' href='#'>削除</a></li></ul></div></div>");

          $('#todo').append("<div class='todoes'><input class='categoryId' type='hidden' value='" + val['categoryId'] + "'><div class='wrap'></div></div>");

          able();
          // hover();
          updateSelect();
        }
      }

      if(false) {
      } else if(action.state == 1) {
        action.queue = action.queue.slice(action.index);
        if(action.queue.length == 0) {
          action.state = 0;
        } else {
          action.index = action.queue.length;
          ajax();
        }
      } else if(action.state == 2) {
        action.state = 3;
        action.queue = action.queue.slice(action.index);
        ajax();

      } else if(action.state == 3) {
        $.unblockUI();
        action.state = 0;
        action.queue = [];
      }
    }
  });
}

function able() {
  var prev, next, sortable = true;
  $('#category .wrap').sortable({
    axis: 'y',
    handle: '.detail',
    // opacity: 0.7,
    distance: 4,
    start: function(event, ui) {
      prev = ui.item.prev().children('input').val();
      next = ui.item.next().next().children('input').val();
    },
    stop: function(event, ui) {
      if(ui.item.prev().children('input').val() == prev &&
         ui.item.next().children('input').val() == next) return;

      updateSelect();

      if(ui.originalPosition.top < ui.position.top) {
        action.queue.push({
          'action': 'downCategory',
          'categoryId': ui.item.children('input').val(),
          'prevCategoryId': ui.item.prev().children('input').val()
        });
      } else {
        action.queue.push({
          'action': 'upCategory',
          'categoryId': ui.item.children('input').val(),
          'nextCategoryId': ui.item.next().children('input').val()
        });
      }
    }
  });

  $('#todo .wrap').sortable({
    grid: [1, 1],
    handle: '.detail',
    // opacity: 0.7,
    distance: 4,
    start: function(event, ui) {
      prev = ui.item.prev().children('input').val();
      next = ui.item.next().next().children('input').val();
    },
    stop: function(event, ui) {
      if(!sortable) {
        sortable = true;
        return;
      }

      if(ui.item.prev().children('input').val() == prev &&
         ui.item.next().children('input').val() == next) return;

      if(ui.originalPosition.top < ui.position.top) {
        action.queue.push({
          'action': 'downTodo',
          'categoryId': $('.category.selected .categoryId').val(),
          'todoId': ui.item.children('input').val(),
          'prevTodoId': ui.item.prev().children('input').val()
        });
      } else {

        action.queue.push({
          'action': 'upTodo',
          'categoryId': $('.category.selected .categoryId').val(),
          'todoId': ui.item.children('input').val(),
          'nextTodoId': ui.item.next().children('input').val()
        });
      }
    }
  });

  $('.category').droppable({
    accept: '.todo',
    hoverClass: 'hover',
    tolerance: 'pointer',
    drop: function(ev, ui) {
      sortable = false;

      var categoryId = $(this).children('input').val();
      var todoId = ui.draggable.children('input').val();
      $("#todo .categoryId[value='" + categoryId + "']").next().prepend("<div class='todo'>" + ui.draggable.html() + "</div>");
      ui.draggable.remove();

      action.queue.push({
        'action': 'changeCategory',
        'categoryId': categoryId,
        'todoId': todoId
      });
    }
  });
}

// function hover() {
//   $('.todo, .category').hover(function() {
//     $(this).find('.icon-cog').css('opacity', 0.2);
//   }, function() {
//     $(this).find('.icon-cog').css('opacity', 0);
//   });
// }
