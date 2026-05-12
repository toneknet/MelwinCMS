<!DOCTYPE html>
<html lang="se">
<head>

  <!-- Basic Page Needs
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset="utf-8">
  <title><?=$title;?></title>
  <meta name="description" content="">
  <meta name="author" content="">

  <!-- Mobile Specific Metas
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- FONT
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

  <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.0/themes/smoothness/jquery-ui.min.css" integrity="sha512-B2s07Riaz6vCNqR1AsLDeXzzVUWrXwBO+Ffr85wHIH3oKKxXJZjyWLRB5S0Ch7L74fQHY+9L36nTpkB5oKJZcA==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.0/themes/smoothness/theme.min.css" integrity="sha512-7mcknzoAt7kslAEhAgPbDFLOniKNseKUQcSUaErgnt5cDTTMWnC8oFaAubmy4e8azw4hNaIikeFXGjI1LupqtQ==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->

  <link rel="stylesheet" href="<?=URLBASE;?>/tpl/skeleton/css/normalize.css">
  <link rel="stylesheet" href="<?=URLBASE;?>/tpl/skeleton/css/skeleton.css">
  <?=$css;?>
  <link rel="stylesheet" href="<?=URLBASE;?>/tpl/skeleton/style.css">




  <!-- Favicon
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <!-- <link rel="icon" type="image/png" href="<?=URLBASE;?>/tpl/skeleton/images/favicon.png"> -->


<!--  <script src = "https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>-->
  <!-- <script src = "https://code.jquery.com/jquery-3.7.1.min.js"></script> -->
  <!-- <script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script> -->
  <script
  src="https://code.jquery.com/jquery-3.7.1.js"
  integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
  crossorigin="anonymous"></script>

  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>-->
<!--  <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>-->
  <script src="<?=URLBASE;?>/resources/js/jquery.tablednd.js"></script>
  <!-- <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script> -->
  <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.min.js" integrity="sha256-Fb0zP4jE3JHqu+IBB9YktLcSjI1Zc6J2b6gTjB0LpoM=" crossorigin="anonymous"></script>

<!--  <script src="//code.jquery.com/ui/1.11.3/jquery-ui.js"></script>-->
<!--    <script src="<?=URLBASE;?>/resources/js/jquery.colorbox.min.js"></script>-->

  <!-- Spectrum colorpicker -->
  <script src="<?=URLBASE;?>/resources/plugins/colorpicker/spectrum.js"></script>
  <link rel="stylesheet" href="<?=URLBASE;?>/resources/plugins/colorpicker/spectrum.css">
    <!-- /Spectrum colorpicker -->

<link rel="stylesheet" href="<?=URLBASE;?>/resources/plugins/fancybox/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
<script type="text/javascript" src="<?=URLBASE;?>/resources/plugins/fancybox/jquery.fancybox.pack.js?v=2.1.5"></script>

<!-- Mobil menu?  -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script>
  var URLroot = "<?=URLBASE;?>/";
</script>
 <?=$js;?>

  <script src="<?=URLBASE;?>/resources/js/common.php?v=1.0"></script>


  <script>
$(document).ready(function()
{
    // scrollTop sätter vertikalt
    // scrollLeft sätter horisontalt
$("#cal_year_div_month").scroll(function () {
        $("#cal_year_div_day").scrollTop($("#cal_year_div_month").scrollTop());
//        $("#cal_year_div_day").scrollLeft($("#cal_year_div_month").scrollLeft());
    });
$("#cal_year_div_day").scroll(function () {
        $("#cal_year_div_month").scrollTop($("#cal_year_div_day").scrollTop());
        $("#cal_year_div_weekday").scrollLeft($("#cal_year_div_day").scrollLeft());
    });

$("#cal_year_div_weekday").scroll(function () {
//        $("#cal_year_div_month").scrollTop($("#cal_year_div_day").scrollTop());
        $("#cal_year_div_day").scrollLeft($("#cal_year_div_weekday").scrollLeft());
    });



});

</script>




</head>
<body>
  <div id="page_overlay" style="display:none;"><i class="fa fa-spinner fa-spin" style="font-size:48px;"></i></div>
<div class="se-pre-con"></div>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <div class="container ">
    <div class="row">

		<div class="twelve columns noprint">
			<h1 class="remove-bottom" style="margin-top: 40px"><?php print $title;?></h1>
			<!--<h5>Version 1.2</h5>
			<hr />-->
		</div>
		<div class="twelve columns noprint">
            <?php print $navigation;?>
		</div>
        <div id="AjaxResult"  class="twelve columns noprint"></div>
		<div class="twelve columns">
            <?php print $main;?>
		</div>
		<div class="twelve columns footer noprint">
            <?php print $footer;?>
            &copy; 2006-<?php print date("Y");?> <?= $GLOBALS['CONFIG']['tpl_domain'] ?>. All Rights Reserved. For demonstration or order, please contact us at '<?= $GLOBALS['CONFIG']['tpl_email'] ?>'.
		</div>

        <!--
      <div class="one-half column" style="margin-top: 25%">
        <h4>Basic Page</h4>
        <p>This index.html page is a placeholder with the CSS, font and favicon. It's just waiting for you to add some content! If you need some help hit up the <a href="http://www.getskeleton.com">Skeleton documentation</a>.</p>
      </div>-->
    </div>
  </div>
<?php print $outside;?>
<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
</body>
</html>
