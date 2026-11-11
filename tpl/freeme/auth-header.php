<?php
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$loginText = $GLOBALS['LANG']['login'];
$base = rtrim(URLBASE, '/');
$assets = $base . '/tpl/freeme';
$icon = $GLOBALS['CONFIG']['freeme_icon'] ?? ($assets . '/icon.svg');
?>
<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $escape($authHeading); ?> | <?= $escape($title); ?></title>
  <link rel="stylesheet" href="<?= $escape($assets); ?>/base.css">
  <link rel="stylesheet" href="<?= $escape($assets); ?>/style.css">
</head>
<body class="freeme freeme-auth">
  <main class="freeme-main freeme-auth-card">
    <div class="freeme-auth-brand">
      <img src="<?= $escape($icon); ?>" alt="" width="56" height="48">
      <span><?= $escape($title); ?></span>
    </div>
    <h1><?= $escape($authHeading); ?></h1>
