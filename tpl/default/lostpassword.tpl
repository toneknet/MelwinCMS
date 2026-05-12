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
  <main class="login">
    <form action="?action=lostpassword&subaction=ask" id="frmLogin" class="frmLogin" method="post">
      <h1><?=$title;?></h1><h2><?=$GLOBALS['LANG']['login']['frmLostpassword'];?></h2>
      <?=$frmMsg;?>
      <input type="text" id="username" name="username" autocomplete="off" value="" required="" placeholder="<?=$GLOBALS['LANG']['login']['email'];?>">
      <label for="solution"><?=$math;?></label>
      <input type="text" name="solution" placeholder="<?=$GLOBALS['LANG']['login']['math'];?>" >

      <input type="submit" value="<?=$GLOBALS['LANG']['login']['btnLostpassword'];?>">
      <div class="login__subs">
        <?=$register_links;?>
      </div>
    </form>
  </main>
</body>
</html>