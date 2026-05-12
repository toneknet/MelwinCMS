<?php
header('Content-type: text/javascript');
include_once("../../system/configs/config.inc.php");
include_once("../../system/configs/localconfig.inc.php");
print "var URLroot = \"" . $GLOBALS['CONFIG']['www-root'] . "/\";\n";
// Språkfil här?
//print "/*\n";
//(print_r($GLOBALS['tpl']['js']);
//print "\n*/\n";
//print time();
//print "\n";
/*




 */
?>

// function page_overlay_on() {
//   $('#page_overlay').show();
//   console.log('show overlay');
//   // document.getElementById("page_overlay").style.display = "block";
// }

// function page_overlay_off() {
//   $('#page_overlay').hide();
//   console.log('hide overlay');
//   // document.getElementById("page_overlay").style.display = "none";
// }

/*
 * DnD
 */
$(document).ready(function() {

    $('.table-3').tableDnD({
        onDrop: function(table, row) {
            //alert("Result of $.tableDnD.serialise() is "+$.tableDnD.serialize());
            var tablename = $( "#dbtable" ).text();
//            alert(str);
            console.log($.tableDnD.serialize());
            $('#AjaxResult').load(URLroot + "system/ajax/ajaxRequest.php?action=system&subaction=reorder&table=" + tablename + "&"+$.tableDnD.serialize());
        }
    });

//    $(".alert-box").show().delay(3000).fadeOut('slow');

    $(function() {
        $( "#tabs" ).tabs();
    });
    $(function() {
        $( "#tabz" ).tabs();
    });

    $( ".cl_tabz" ).click(function(e) {
        $('.db_tabz').val($(e.target).attr('name'));
    });

    $( ".cl_tabs" ).click(function(e) {
        $('.db_tabs').val($(e.target).attr('name'));
    });

    // Ajax för bokning
    /*$( "[name^='db_']" ).change(function(e) {
      console.log("Changed");
        var tablename = $( "[name='table']" ).val();
        if (tablename == 'booking') {
            var id = $( "[name='db_id']" ).val();
            var hash = $( "[name='hash']" ).val();
            var bus = $( "[name='db_bus']" ).val();
            var startdate = $( "[name='db_startdate']" ).val();
            var starttime = $( "[name='db_starttime']" ).val();
            var stopdate = $( "[name='db_stopdate']" ).val();
            var stoptime = $( "[name='db_stoptime']" ).val();
            //console.log(URLroot + "system/ajax/ajaxRequest.php?action=booking&table=" + tablename, {id:id, bus:bus, hash:hash, startdate:startdate, starttime:starttime, stopdate:stopdate, stoptime:stoptime});
            $.post(URLroot + "system/ajax/ajaxRequest.php?action=system&subaction=booking&table=" + tablename, {id:id, bus:bus, hash:hash, startdate:startdate, starttime:starttime, stopdate:stopdate, stoptime:stoptime}, function(result){
                if (result.length>10) {
                    console.log(result);
                    //console.log('FELAKTIGT SVAR');
                };
            }, "html");

        };
    });*/

});


$(document).ready(function() {
	$(".various").fancybox({
		maxWidth	: 800,
		maxHeight	: 600,
		fitToView	: false,
		width		: '70%',
		height		: '70%',
		autoSize	: false,
		closeClick	: false,
		openEffect	: 'none',
		closeEffect	: 'none'
	});

//    $('.SelectorToPrint').printElement();

});


$(document).ready(function() {
    $('#fixa_alla_b').click(function() {
       var antal=0;
        $(":input").each(function(){
            var text = $(this).val();
            var textx = text.replace("(B)", "B");
            $(this).val(textx);
//            if (textx != text) {
//                antal=antal+1;
//            }
        });
        alert("Klar! ");
    });
});





// MODAL
/**
 * Creates a modal that is possible to add html or just a spinner
 * requires font-awesome css lib
 * 
 * @param html The html to add to modal. If left empty a spinner appears.
 */
function createModalM(html='')  {
  console.log('<!-- Modal by MrSlade. www.tonek.se -->');
  if (html == '') { html += "<i class='fa fa-spinner fa-spin' style='font-size:48px;'></i>"; }
  $( "body" ).prepend( "<div id='modalByMrslade' style='position: fixed; display: flex; width: 100%; height: 100%; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.9); z-index: 2; cursor: pointer; align-items: center; justify-content: center;flex-direction: column;'>" + html + "</div>" );
}
/**
 * Closes the modal created by createModal
 */
function closeModalM() {
  $( "#modalByMrslade" ).remove();
}




function pageOverlay(status=0) {
  if(status == 0) {
    $('#page_overlay').css('display', 'none');
  } else if( status == 1) {
    $('#page_overlay').css('display', 'flex');
  }
  console.log('status',status);
}


// $.newFunction = function(arg){
//   alert("this is nice " + arg);
// }



function confirmDelete(ID) {
	if ( confirm( "Vill du verkligen radera denna post med ID " + ID + "?" ) ) {
		return true;
	} else {
		return false;
	}
};

function confirmDeleteMSG(msg) {
  //console.log(msg);
	if ( confirm( msg  ) ) {
		return true;
	} else {
    //preventDefault();
		return false;
	}
};


function confirmQ() {
	if ( confirm( "Är du säker på att du vill göra detta?" ) ) {
		return true;
	} else {
		return false;
	}
};


   function printDiv(divId) {
       var printContents = document.getElementById(divId).innerHTML;
       var originalContents = document.body.innerHTML;
       document.body.innerHTML = "<html><head><title></title></head><body>" + printContents + "</body>";
       window.print();
       document.body.innerHTML = originalContents;
   };

// Fixar datum på slut
$(document).ready(function() {
    $('#fixa_alla_b').click(function() {
       var antal=0;
        $(":input").each(function(){
            var text = $(this).val();
            var textx = text.replace("(B)", "B");
            $(this).val(textx);
//            if (textx != text) {
//                antal=antal+1;
//            }
        });
        alert("Klar! ");
    });
});








// Fixar konstiga tecken..
$(document).ready(function() {
    $('input').keypress(function (e) {
        var theReturn = allowedChars(e.charCode);
        if (theReturn == true) {
            return true;
        };
        e.preventDefault();
        return false;
    });

    $('textarea').keypress(function (e) {
//    console.log(e.charCode);
        var theReturn = allowedChars(e.charCode);
        if (theReturn == true || e.charCode == 13 ) {
            return true;
        };
        e.preventDefault();
        return false;
    });

});
// Allowed characters
function allowedChars(charCode) {
        console.log(charCode);
        // 0-9
        if (charCode >= 48 && charCode <= 57) {
//            console.log("true");
            return true;
        }
        // a-z
        if (charCode >= 97 && charCode <= 122) {
//            console.log("true");
            return true;
        }
        // A-Z
        if (charCode >= 65 && charCode <= 90) {
//            console.log("true");
            return true;
        }
        // å,ä,ö
        if (charCode == 228 || charCode == 229 || charCode == 246 ) {
//            console.log("true");
            return true;
        }
        // Å,Ä,Ö
        if (charCode == 197 || charCode == 196 || charCode == 214 ) {
//            console.log("true");
            return true;
        }
        // . , ? ! == - _ + ) ( / & % ¤ # @
        if (charCode == 46 ||
            charCode == 44 ||
            charCode == 63 ||
            charCode == 33 ||
            charCode == 61 ||
            charCode == 45 ||
            charCode == 95 ||
            charCode == 43 ||
            charCode == 40 ||
            charCode == 41 ||
            charCode == 47 ||
            charCode == 38 ||
            charCode == 37 ||
            charCode == 164 ||
            charCode == 35 ||
            charCode == 59 ||
            charCode == 32 ||
            charCode == 64 ||
            charCode == 58 ) {
//            console.log("true");
            return true;
        }
        // Enter
        if (charCode == 13 ) {
            return true;
        }
        return false;
};

function toggleDiv(divId, lblText) {
    $("#" + divId + "_content").toggle();
    if ($("#" + divId + "_content").css('display') == 'none') {
        //change the button label to be 'Show'
        $("#" + divId + "_legend").html("&#9658 " + lblText);
        setCookie(divId ,0,99999);
    } else {
        //change the button label to be 'Hide'
        $("#" + divId + "_legend").html("&#9660 " + lblText);
        setCookie(divId ,1,99999);
    }

}

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
  let name = cname + "=";
  let ca = document.cookie.split(';');
  for(let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

function checkLegends(cname,lblText) {
  let user = getCookie(cname);
  if (user) {
    if (user == 0) {
      toggleDiv(cname,lblText);
    }
  }
}





$(document).ready(function() {
    $('#kolla_alla_b').click(function() {
      console.log("Klickad");
    });



    /**
     * Allow only Numbers and a single DOT.
     */
    $('.onlyNumbersAndDots').on('keypress', function (evt) {
      console.log('test', evt);
      var charCode = (evt.which) ? evt.which : evt.keyCode;
      console.log(charCode);
        if (charCode == 46) {
          //Check if the text already contains the . character
          if ($(this).val().indexOf('.') === -1) {
            return true;
          } else {
            return false;
          }
        } else {
          if (charCode > 31 &&
            (charCode < 48 || charCode > 57))
            return false;
        }
        return true;


    });


    // $("input[maxlength]").on('keypress', function (evt) {


    //   console.log(
    //     $(this).attr("maxlength"),
    //     $(this).val().length
      

    //   );
    //   $(this).after("<span class=\"input-group-addon maxlength\"></span>");
    //   $(this).next(".maxlength").text($(this).val().length.toString());
    // });

    !function ($) {
      $.fn.maxlength = function() {
        $(this).each(function() {
          if ($(this).attr('type') == 'hidden' || $(this).attr('type') == 'checkbox' ) return;
          var max = $(this).attr('maxlength');

          if (max <= 0) {
            return;
          }

          if (!$(this).parent().hasClass('input-group')) {
            $(this).wrap("<div class=\"input-group\"></div>");
          }
          $(this).after("<span class=\"input-group-addon maxlength\"></span>");

          $(this).bind('input', function(e) {
            var max = $(this).attr('maxlength');
            var val = $(this).val();
            var cur = 0;

            if (val) {
              cur = val.length;
            }

            var left = max-cur;

            $(this).next(".maxlength").text(cur.toString() + "/" + max.toString() );

            return this;
          }).trigger('input');
        });
        return this;
      };
    }(window.jQuery);

    // $("input[maxlength]").maxlength();

});


//
// Checks if ROW has all routes
//
// 741,743,744,749,756,757,758,768,769,770
$(document).ready(function() {
    $('#check_routes').click(function() {
      $(".clsRow > span").each(function(){
        var row = $(this).attr('class');
        //console.log( row );
        var routes = ["741", "743", "744", "749", "756", "757", "758", "768", "769", "770", "Mat1", "Mat2"]; //, "Mat3"
        var skola = "Skola";
        var totalSkola = 0;
        $("input." + row).each(function(){
          var fLen, i;
          fLen = routes.length;
          var v = $(this).val();
          //console.log(v.indexOf(skola));
          if (v.indexOf(skola)>-1){
            totalSkola++;
          }
          console.log(row + ' : ' +v);
          for (i = 0; i < fLen; i++) {
            var n = v.indexOf(routes[i]);
            if (n>-1) {
              //console.log('Found ' + routes[i] + ' in ' + row);
              routes[i] = "";
//              delete routes[i];
//              i--;
//              fLen = routes.length;
            }
          }
        });
          // Vad är kvar?
          var text = '';
          fLen = routes.length;
          for (i = 0; i < fLen; i++) {
            if (routes[i] != "") {
//              text+=routes[i] + "<br />";
              text+=routes[i] + ",<br />\n ";
//              text+="<br />" + routes[i];
            }
            //console.log('REST ' + routes[i] + ' on ' + row);
          }
          if (totalSkola>0) {
            text+="<br />Skola: " + totalSkola;
          }
          if (text != "") {
            $('span.' + row).html(text);
            $('span.' + row).css('color','#ff0000');
            $('span.' + row).css('text-decoration','none');
            //$('span.' + row).css('border','2px solid #ff0000');
          }




      });

//      console.log('Klickade...');
//       var antal=0;
//        $(":input").each(function(){
//            var text = $(this).val();
//            var textx = text.replace("(B)", "B");
//            $(this).val(textx);
//            if (textx != text) {
//                antal=antal+1;
//            }
//        });
//        alert("Klar! ");
    });
});

$(document).ready(function() {
    //$('#check_routes').click(function() {
    //  console.log("HEY");
//  $("input[type=text]").on('focus keyup change', function() {
//    // Check if span exist else add
//    if ($(this).closest("span.counter").length) {
//      console.log("Found Span");
////      // Add
//      $(this).after("<span class=\"counter\">0</span>");
//      //$("<span class=\"counter\">0</span>").insertAfter($(this));
//      let span = $(this).closest("span.counter");
//    } else {
//      console.log("No Span Found");
////      let span = $(this).closest("span.counter");
//    }
//    let currentLength = $(this).val().length;
//    let maxLength = $(this).attr('maxlength');
//    let LengthLeft = maxLength - currentLength;
////    span.innerHTML = LengthLeft;
//    console.log(LengthLeft);
//    console.log($(this).attr('name'));
//  });
  // On blur remove span



});


// form
$(document).ready(function() {
      $('.isFileName_trashcan').click(function(e) {
        console.log("Clicked the Trash");
        var cls = '#' + $(this).data("id");
        // Rensar span och fält
        $(cls).val('');
        $(this).prev('span').text('');
        // Gömmer ikon
        var spanid = cls.slice(0,-8) + 'span';
        $(spanid).hide();
        var inputid = cls.slice(0,-9);
        $(inputid).show();
        //console.log(inputid);
        //console.log(spanid);




        //$(this).prev('span').prev('span').hide();
        //console.log(this.getAttribute("title"));
        //console.log(cls);
        //console.log($(cls).val());
        //console.log($(this).prev('span').text());
        // isFileName_Input, db_bakgrund_filename
        //var dta = $(this).prev('input').getAttribute("name"); //closest
        //console.log(dta);
        //console.log( e.getAttribute("title") );
        //$(".clsRow > span").each(function(){ });
      });







});



$(document).ready(function() {
  // db_namn, table, db_user, db_shortdriver
  $('#db_namn').change(function(e) {
    if ($('#table').val() !== 'user') {return;}
    //console.log("Leaved");
    var name = $(this).val();
    if (name.indexOf(' ') >= 0) {
      const nameArray = name.split(" ");
      var together = nameArray[0].substring(0, 3) + nameArray[1].substring(0, 3);
      together = together.toLowerCase();
      together = together.replaceAll("Å","a");
      together = together.replaceAll("Ä","a");
      together = together.replaceAll("Ö","o");
      together = together.replaceAll("å","a");
      together = together.replaceAll("ä","a");
      together = together.replaceAll("ö","o");
      var shortdriver = nameArray[0].substring(0, 1) + nameArray[1].substring(0, 1);
      shortdriver = shortdriver.toUpperCase();
    }
    var user = $("#db_user").val();
    if (user == "" && typeof together !== 'undefined') {
      $("#db_user").val(together).change();
    }
    //db_shortdriver
    var driver = $("#db_shortdriver").val();
    if (driver == "" && typeof shortdriver !== 'undefined') {
      $("#db_shortdriver").val(shortdriver).change();
    }


    console.log(user);
    console.log(name,together);
  });

  // Check for duplicates
  $('#db_user').change(function(e) {
    console.log("U HEY");
    // Kolla om id är satt först.
    var id = $("#db_id").val();
    var user = $(this).val();
    var hash = $("hash").val();
    $.get(URLroot + "system/ajax/ajaxRequest.php?action=checkusername&id=" + id + "&user=" + user, function(data, status){
      const obj = JSON.parse(data);
      //console.log($( ".user_icon" ).length);
      if ( $( ".user_icon" ).length == 0 ) {
        $( "#db_user" ).after( '<span class="user_icon" style="padding:10px;"></span>' );
      }
      if (obj.type == 1) { console.log('OK!'); $( ".user_icon" ).html( '<img src="<?= $GLOBALS['CONFIG']['www-root'] ?>/resources/icons/ok.png" width="16" height="16">' );};
      if (obj.type == 0) { console.log('Fail!'); $( ".user_icon" ).html( '<img src="<?= $GLOBALS['CONFIG']['www-root'] ?>/resources/icons/exclamation.png" width="16" height="16">' );};
    });

    console.log("done?");
  });

  // Check for duplicates - checkuserinitials
  $('#db_shortdriver').change(function(e) {
    console.log("S HEY");
    // Kolla om id är satt först.
    var id = $("#db_id").val();
    var initials = $(this).val();
    var hash = $("hash").val();
    $.get(URLroot + "system/ajax/ajaxRequest.php?action=checkuserinitials&id=" + id + "&initials=" + initials, function(data, status){
      const obj = JSON.parse(data);
      //console.log($( ".initials_icon" ).length);
      if ( $( ".initials_icon" ).length == 0 ) {
        $('#db_shortdriver').after( '<span class="initials_icon" style="padding:10px;"></span>' );
      }
      if (obj.type == 1) { console.log('OK!'); $( ".initials_icon" ).html( '<img src="<?= $GLOBALS['CONFIG']['www-root'] ?>/resources/icons/ok.png" width="16" height="16">' );};
      if (obj.type == 0) { console.log('Fail!'); $( ".initials_icon" ).html( '<img src="<?= $GLOBALS['CONFIG']['www-root'] ?>/resources/icons/exclamation.png" width="16" height="16">' );};
    });

    console.log("done?");
  });


});


  function ajaxRequest(type,field,xtra = '') {
    console.log("ajaxRequest",type,field,xtra);
    $('#jt_status').load(URLroot + "system/ajax/ajaxRequest.php?action=system_update_tool&type=" + type + "&field=" + field + xtra, function(responseTxt, statusTxt, xhr){
      console.log(responseTxt,statusTxt,xhr);
      //if(statusTxt == "success")
        //$("#jt_myModal").show().delay(1000).fadeOut();
        //console.log("External content loaded successfully!");
        //if(statusTxt == "error")
        //  console.log("Error: " + xhr.status + ": " + xhr.statusText);
    });
  }



/**
 * jQuery Plugin: formatNumberInput
 * 
 * This plugin extends jQuery to format input fields to accept only numeric values
 * and format them to two decimal places when the input field loses focus.
 * 
 * Usage:
 * $('input').formatNumberInput();
 * 
 * @param {Object} $ - jQuery object
 */
(function($) {
  $.fn.formatNumberInput = function() {
      this.on('input', function() {
          // Tillåt endast nummer och punkt
          this.value = this.value.replace(/[^0-9.]/g, '');
      });

      this.on('blur', function() {
          // Formatera värdet som #.##
          let value = parseFloat(this.value);
          if (!isNaN(value)) {
              this.value = value.toFixed(2);
          }
      });

      return this;
  };
}(jQuery));
