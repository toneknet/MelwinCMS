<?php
$authHeading = $GLOBALS['LANG']['login']['frmLostpassword'];
require TPLPATH . 'freeme/auth-header.php';
?>
<form action="?action=lostpassword&amp;subaction=ask" id="frmLogin" method="post">
  <?= $frmMsg ?? ''; ?>
  <label for="username"><?= $escape($loginText['email']); ?></label>
  <input id="username" name="username" type="text" autocomplete="email" required>
  <label for="solution"><?= $math; ?></label>
  <input id="solution" name="solution" type="text" inputmode="numeric" placeholder="<?= $escape($loginText['math']); ?>">
  <button type="submit" class="button-primary"><?= $escape($loginText['btnLostpassword']); ?></button>
  <div class="freeme-auth-links"><?= $register_links ?? ''; ?></div>
</form>
  </main>
</body>
</html>
