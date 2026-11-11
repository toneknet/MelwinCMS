<?php
$authHeading = $GLOBALS['LANG']['login']['frmChangepassword'];
require TPLPATH . 'freeme/auth-header.php';
?>
<form action="?action=lostpassword&amp;subaction=retrieve&amp;code=<?= $escape(rawurlencode((string) $code)); ?>" id="frmLogin" method="post">
  <?= $frmMsg ?? ''; ?>
  <label for="email"><?= $escape($loginText['email']); ?></label>
  <input id="email" name="email" type="text" autocomplete="email" required>
  <label for="password1"><?= $escape($loginText['password_new']); ?></label>
  <input id="password1" name="password1" type="password" autocomplete="new-password" required>
  <label for="password2"><?= $escape($loginText['password2']); ?></label>
  <input id="password2" name="password2" type="password" autocomplete="new-password" required>
  <button type="submit" class="button-primary"><?= $escape($loginText['btnChangepassword']); ?></button>
  <div class="freeme-auth-links"><?= $register_links ?? ''; ?></div>
</form>
  </main>
</body>
</html>
