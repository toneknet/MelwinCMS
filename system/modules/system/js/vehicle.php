$(document).ready(function() {
  // vehicle
  $(".frm_b_name").after('<a href="#" id="get_car_info"><img src="<?php
  if (isset($GLOBALS["CONFIG"]["www-root"])) print $GLOBALS["CONFIG"]["www-root"];
  print "/resources/icons/car.svg";?>" style="height: 100%;vertical-align: middle;" class="geticons"></a>');
  $("#get_car_info").on('click', function(e) {
    e.preventDefault();
    //console.log("HEY");
    var regnr = $( "#db_regnr" ).val();
    //console.log(regnr);

    $.ajax({url: URLroot + "system/ajax/ajaxRequest.php?action=getCarModel&regnr=" + regnr.replace(/\s/g,'') , success: function(result){
      //console.log(result);
      //  if (result == '1') {
      //     $("."+field+"_hide").hide();
      //  }
      $(".frm_b_name").val(result);
    }});

    //$('.frm_b_name').load(URLroot + "system/ajax/ajaxRequest.php?action=getCarModel&regnr=" + regnr.replace(/\s/g,''));
  });
});
