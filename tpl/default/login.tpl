<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?=$title;?></title>
  <link rel="stylesheet" href="<?=$GLOBALS['CONFIG']['www-root'];?>/tpl/default/style.css">
</head>
<body>
  <!-- <?php #$this->header('default');?> -->
  <main class="login">
        <form action="?action=login" id="frmLogin" class="frmLogin" method="post">
          <h1><?=$title;?></h1><h2><?=$GLOBALS['LANG']['login']['frmLogin'];?></h2>
          <?=$error;?><?=$frmMsg;?>
          <input type="text" id="username" name="username" autocomplete="username" value="" required="" placeholder="<?=$GLOBALS['LANG']['login']['username'];?>">
          <input type="password" autocomplete="current-password" id="password" name="password" value="" required="" placeholder="<?=$GLOBALS['LANG']['login']['password'];?>">
          <input type="submit" value="<?=$GLOBALS['LANG']['login']['btnLogin'];?>">
          <div class="login__subs">
            <?=$register_links;?>
          </div>
        </form>
  </main>
  <!-- <?php #$this->footer('default');?> -->
</body>
</html>