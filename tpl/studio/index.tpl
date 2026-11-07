<?php
// Keep module HTML intact; only configuration text is escaped here.
$studioEscape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$studioBase = rtrim(URLBASE, '/');
?>
<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= $studioEscape($title); ?></title>
  <!-- Retain the grid and module utilities used by existing screens. -->
  <link rel="stylesheet" href="<?= $studioEscape($studioBase); ?>/tpl/skeleton/css/normalize.css">
  <link rel="stylesheet" href="<?= $studioEscape($studioBase); ?>/tpl/skeleton/css/skeleton.css">
  <link rel="stylesheet" href="<?= $studioEscape($studioBase); ?>/tpl/skeleton/style.css">
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
  <link rel="stylesheet" href="<?= $studioEscape($studioBase); ?>/resources/plugins/colorpicker/spectrum.css">
  <link rel="stylesheet" href="<?= $studioEscape($studioBase); ?>/resources/plugins/fancybox/jquery.fancybox.css?v=2.1.5">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <?= $css; ?>
  <link rel="stylesheet" href="<?= $studioEscape($studioBase); ?>/tpl/studio/style.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.min.js" integrity="sha256-Fb0zP4jE3JHqu+IBB9YktLcSjI1Zc6J2b6gTjB0LpoM=" crossorigin="anonymous"></script>
  <script src="<?= $studioEscape($studioBase); ?>/resources/js/jquery.tablednd.js"></script>
  <script src="<?= $studioEscape($studioBase); ?>/resources/plugins/colorpicker/spectrum.js"></script>
  <script src="<?= $studioEscape($studioBase); ?>/resources/plugins/fancybox/jquery.fancybox.pack.js?v=2.1.5"></script>
  <script>var URLroot = <?= json_encode($studioBase . '/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
  <?= $js; ?>
  <script src="<?= $studioEscape($studioBase); ?>/resources/js/common.php?v=1.0"></script>
  <script src="<?= $studioEscape($studioBase); ?>/tpl/studio/studio.js" defer></script>
</head>
<body class="studio">
  <a class="studio-skip" href="#studio-main">Hoppa till innehållet</a>
  <div id="page_overlay" style="display:none;"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i><span>Arbetar…</span></div>
  <div class="studio-shell">
    <aside class="studio-sidebar noprint" aria-label="Sidomeny">
      <a class="studio-brand" href="<?= $studioEscape($studioBase); ?>/" aria-label="Startsida"><span class="studio-mark" aria-hidden="true">m<span>.</span></span><span>Melwin<span class="studio-brand-sub">STUDIO / CMS</span></span></a>
      <div class="studio-nav-label">Arbetsyta <span aria-hidden="true">↗</span></div>
      <div class="studio-navigation" id="studio-navigation"><?= $navigation; ?></div>
      <div class="studio-sidebar-foot"><span class="studio-dot" aria-hidden="true"></span> Plats för det viktiga.</div>
    </aside>
    <div class="studio-workspace">
      <header class="studio-topbar noprint">
        <span class="studio-eyebrow">DIN ARBETSYTA</span>
        <a class="studio-home" href="<?= $studioEscape($studioBase); ?>/">Till startsidan <span aria-hidden="true">↗</span></a>
      </header>
      <div class="studio-heading noprint"><span class="studio-eyebrow">ÖVERBLICK &amp; KONTROLL</span><h1><?= $studioEscape($title); ?><span class="studio-title-dot">.</span></h1><?= $header; ?></div>
      <div id="AjaxResult" class="studio-feedback noprint" aria-live="polite"></div>
      <main class="studio-main" id="studio-main" tabindex="-1"><?= $main; ?></main>
      <footer class="studio-footer noprint"><div><?= $footer; ?></div><span>© <?= date('Y'); ?> <?= $studioEscape($GLOBALS['CONFIG']['tpl_domain'] ?? 'MelwinCMS'); ?></span><span class="studio-footer-sign">En tydligare arbetsdag<span aria-hidden="true"> ↗</span></span></footer>
    </div>
  </div>
  <?= $outside; ?>
</body>
</html>
