<?php
$authHeading = $GLOBALS['LANG']['login']['frmLogin'];
require TPLPATH . 'freeme/auth-header.php';
?>
<form action="?action=login" id="frmLogin" method="post">
  <?= $error ?? ''; ?><?= $frmMsg ?? ''; ?>
  <label for="username"><?= $escape($loginText['username']); ?></label>
  <input id="username" name="username" type="text" autocomplete="username" required>
  <label for="password"><?= $escape($loginText['password']); ?></label>
  <input id="password" name="password" type="password" autocomplete="current-password" required>
  <button type="submit" class="button-primary"><?= $escape($loginText['btnLogin']); ?></button>
  <div class="freeme-auth-links"><?= $register_links ?? ''; ?></div>
</form>
  </main>
</body>
</html>
