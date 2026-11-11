<?php
// Keep module HTML intact; only configuration text is escaped here.
$freemeEscape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$freemeBase = rtrim(URLBASE, '/');
$freemeIcon = $GLOBALS['CONFIG']['freeme_icon'] ?? ($freemeBase . '/tpl/freeme/icon.svg');
?>
<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= $freemeEscape($title); ?></title>
  <link rel="stylesheet" href="<?= $freemeEscape($freemeBase); ?>/tpl/freeme/base.css">
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
  <link rel="stylesheet" href="<?= $freemeEscape($freemeBase); ?>/resources/plugins/colorpicker/spectrum.css">
  <link rel="stylesheet" href="<?= $freemeEscape($freemeBase); ?>/resources/plugins/fancybox/jquery.fancybox.css?v=2.1.5">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <?= $css; ?>
  <link rel="stylesheet" href="<?= $freemeEscape($freemeBase); ?>/tpl/freeme/style.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.min.js" integrity="sha256-Fb0zP4jE3JHqu+IBB9YktLcSjI1Zc6J2b6gTjB0LpoM=" crossorigin="anonymous"></script>
  <script src="<?= $freemeEscape($freemeBase); ?>/resources/js/jquery.tablednd.js"></script>
  <script src="<?= $freemeEscape($freemeBase); ?>/resources/plugins/colorpicker/spectrum.js"></script>
  <script src="<?= $freemeEscape($freemeBase); ?>/resources/plugins/fancybox/jquery.fancybox.pack.js?v=2.1.5"></script>
  <script>var URLroot = <?= json_encode($freemeBase . '/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
  <?= $js; ?>
  <script src="<?= $freemeEscape($freemeBase); ?>/resources/js/common.php?v=1.0"></script>
  <script src="<?= $freemeEscape($freemeBase); ?>/tpl/freeme/freeme.js" defer></script>
</head>
<body class="freeme">
  <a class="freeme-skip" href="#freeme-main">Hoppa till innehållet</a>
  <div id="page_overlay" style="display:none;"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i><span>Arbetar…</span></div>
  <div class="freeme-shell">
    <aside class="freeme-sidebar noprint" aria-label="Sidomeny">
      <a class="freeme-brand" href="<?= $freemeEscape($freemeBase); ?>/" aria-label="Startsida">
        <img src="<?= $freemeEscape($freemeIcon); ?>" alt="" width="56" height="48">
      </a>
      <div class="freeme-navigation" id="freeme-navigation"><?= $navigation; ?></div>
    </aside>
    <div class="freeme-workspace">
      <header class="freeme-heading noprint">
        <div class="freeme-width"><h1><?= $freemeEscape($title); ?></h1><?= $header; ?></div>
      </header>
      <div id="AjaxResult" class="freeme-feedback freeme-width noprint" aria-live="polite"></div>
      <main class="freeme-main freeme-width" id="freeme-main" tabindex="-1"><?= $main; ?></main>
      <footer class="freeme-footer noprint"><div class="freeme-width"><?= $footer; ?></div></footer>
    </div>
  </div>
  <?= $outside; ?>
</body>
</html>
