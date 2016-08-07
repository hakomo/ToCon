
use hakomo_tocon;

drop table if exists user, category, todo, history;

create table user(
  userId int auto_increment primary key,
  twitterId bigint,
  facebookId bigint,
  sessionId varchar(90),
  createTime datetime,
  updateTime datetime
);
create table category(
  categoryId int auto_increment primary key,
  userId int,
  rankId int,
  category varchar(90),
  hasDeleted int default 0
);
create table todo(
  todoId int auto_increment primary key,
  categoryId int,
  rankId int,
  todo varchar(90),
  note varchar(700) default '',
  createTime datetime,
  updateTime datetime,
  hasChecked int default 0,
  hasOpened int default 0,
  hasDeleted int default 0
);
create table history(
  historyId int auto_increment primary key,
  todoId int,
  updateTime datetime
);
