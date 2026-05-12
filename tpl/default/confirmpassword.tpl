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
            <form action="?action=lostpassword&subaction=retrieve&code=<?=$code;?>" id="frmLogin" class="frmLogin" method="post" autocomplete="off">
              <h1><?=$title;?></h1><h2><?=$GLOBALS['LANG']['login']['frmChangepassword'];?></h2>
              <?=$frmMsg;?>
              <!-- <input autocomplete="false" name="hidden" type="text" style="display:none;"> -->
              <input type="text" id="email" name="email" autocomplete="off" value="" required="" placeholder="<?=$GLOBALS['LANG']['login']['email'];?>">
              <input type="password" name="password1" id="password1" autocomplete="off" value="" required="" placeholder="<?=$GLOBALS['LANG']['login']['password_new'];?>" >
              <input type="password" name="password2" id="password2" autocomplete="off" value="" required="" placeholder="<?=$GLOBALS['LANG']['login']['password2'];?>" >
              <input type="submit" value="<?=$GLOBALS['LANG']['login']['btnChangepassword'];?>">
              <div class="login__subs">
                <?=$register_links;?>
              </div>
            </form>
          </main>
    </body>
</html>