<?php
  session_start();
  require('dbconnect.php');

  if(isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    $_SESSION['time'] = time();
    $members = $db->prepare('SELECT * FROM members WHERE id=?');
    $members->execute(array($_SESSION['id']));
    $member = $members->fetch();
  }else{
    header('Location: login.php');
    exit();
  }

  if (!empty($_POST)){
    if($_POST['message'] !== ""){
      $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_message_id=?, created_at=NOW()');
      $message->execute(array(
        $member['id'],
        $_POST['message'],
        $_POST['reply_message_id']
      )); 
      // リロードするとmessageが重複して入ってしまうため記述する
      header('Location: index.php');
      exit();
    }
  }

  $page = $_REQUEST['page'];
  if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $page >= 1) {  //数字以外、何も入っていない場合はelseにとばす
    $page = $_REQUEST['page'];
  }else {
    $page = 1;  //指定がない場合は1ページ目を表示する
  }
  $counts = $db->query('SELECT COUNT(*) as count FROM posts');
  $count = $counts->fetch();
  $max_page = ceil($count['count'] / 5);  //ceilは切り上げ
  $page = min($page, $max_page);

  $start = ($page - 1) * 5;
  $posts = $db->prepare('SELECT members.name, members.picture, posts.* FROM members, posts WHERE members.id=posts.member_id ORDER BY posts.created_at DESC LIMIT ?,5');
  $posts->bindParam(1, $start, PDO::PARAM_INT);
  $posts->execute();
  
  if(isset($_REQUEST['res'])){
    // 返信の処理
    $res = $db->prepare('SELECT members.name, members.picture, posts.* FROM members, posts WHERE members.id=posts.member_id AND posts.id=?');
    $res->execute(array($_REQUEST['res']));

    $table = $res->fetch();
    $message = '@' . $table['name'] . ' ' . $table['message'];
  }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
    <div style="text-align: right"><a href="logout.php">ログアウト</a></div>
    <form action="" method="post">
      <dl>
        <dt><?php print(htmlspecialchars($member['name'], ENT_QUOTES));?>さん、メッセージをどうぞ</dt>
        <dd>
          <textarea name="message" cols="50" rows="5"><?php print(htmlspecialchars($message, ENT_QUOTES));?></textarea>
          <input type="hidden" name="reply_message_id" value="<?php print(htmlspecialchars($_REQUEST['res'], ENT_QUOTES));?>" />
        </dd>
      </dl>
      <div>
        <p>
          <input type="submit" value="投稿する" />
        </p>
      </div>
    </form>
  
  <?php foreach($posts as $post):?>
    <div class="msg">
    <?php if($post['picture'] !== ""): ?>
      <img src="member_picture/<?php print(htmlspecialchars($post['picture'], ENT_QUOTES));?>" width="48" height="48" alt="<?php print(htmlspecialchars($post['name'], ENT_QUOTES));?>" />
    <?php else:?>
      <img src="./images/no_image.jpg" width="48" height="48" alt="no_image" />
    <?php endif;?>
    <p>
      <?php print(htmlspecialchars($post['message'], ENT_QUOTES));?>
      <span class="name">（<?php print(htmlspecialchars($post['name'], ENT_QUOTES));?>）</span>
      [<a href="index.php?res=<?php print(htmlspecialchars($post['id'], ENT_QUOTES));?>">Re</a>]
    </p>
    <p class="day">
      <a href="view.php?id=<?php print(htmlspecialchars($post['id'], ENT_QUOTES));?>"><?php print(htmlspecialchars($post['created_at'], ENT_QUOTES));?></a>
      
      <?php if($post['reply_message_id'] > 0): ?>
        <a href="view.php?id=<?php print(htmlspecialchars($post['reply_message_id'], ENT_QUOTES));?>">返信元のメッセージ</a>
      <?php endif;?>
      <!-- ログインしているユーザーのid === 投稿した人のid -->
      <?php if($_SESSION['id'] === $post['member_id'] ): ?>
        [<a href="delete.php?id=<?php print(htmlspecialchars($post['id'], ENT_QUOTES));?>"
            style="color: #F33;">削除
        </a>]
      <?php endif;?>
    </p>
    </div>
  <?php endforeach;?>

<ul class="paging">
  <?php if($page > 1):?>
    <li><a href="index.php?page=<?php print($page - 1 );?>">前のページへ</a></li>
  <?php else: ?>
    <li>前のページへ</li>
  <?php endif;?>
  <?php if($page < $max_page):?>
    <li><a href="index.php?page=<?php print($page + 1 );?>">次のページへ</a></li>
  <?php else: ?>
    <li>次のページへ</li>
  <?php endif;?>
</ul>
  </div>
</div>
</body>
</html>
