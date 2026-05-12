<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <link rel="shortcut icon" href="favicon.ico" />
    <title><?=$title;?></title>
    <script
      src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
      integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    ></script>
    <link rel="stylesheet" href="style.css" />
    <?=$css;?>
    <?=$js;?>
  </head>
  <body>
    <div class="header">
      <?=$header;?>
      <?=$navigation;?>
    </div>
    <div class="container">
      <?=$main;?>
    </div>
    <div class="footer">
      <?=$footer;?>
    </div>
  </body>
</html>
