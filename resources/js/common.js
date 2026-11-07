/*
 * IMPORTANT!!!
 * Du måste ange root även här!
 * 
 */
var URLroot = "https://www.hbook.se/";
/*
 * DnD
 */
$(document).ready(function() {
    console.log(URLroot);
    $('.table-3').tableDnD({
        onDrop: function(table, row) {
            console.log('common.js: Not configured correctly use action=system&subaction=');
            //alert("Result of $.tableDnD.serialise() is "+$.tableDnD.serialize());
            var tablename = $( "#dbtable" ).text();
//            alert(str);
            $('#AjaxResult').load(URLroot + "system/ajax/ajaxRequest.php?action=reorder&table=" + tablename + "&"+$.tableDnD.serialize());
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
    $( "[name^='db_']" ).change(function(e) {
        var tablename = $( "[name='table']" ).val();
        if (tablename == 'booking') {
            var id = $( "[name='db_id']" ).val();
            var hash = $( "[name='hash']" ).val();
            var bus = $( "[name='db_bus']" ).val();
            var startdate = $( "[name='db_startdate']" ).val();
            var starttime = $( "[name='db_starttime']" ).val();
            var stopdate = $( "[name='db_stopdate']" ).val();
            var stoptime = $( "[name='db_stoptime']" ).val();

            $.post(URLroot + "system/ajax/ajaxRequest.php?action=booking&table=" + tablename, {id:id, bus:bus, hash:hash, startdate:startdate, starttime:starttime, stopdate:stopdate, stoptime:stoptime}, function(result){
                if (result.length>10) {
                    alert(result);
                };
            }, "html");
        
        };        
    });

});


$(document).ready(function() {
	if (typeof $.fn.fancybox !== 'function') return;
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

function confirmDelete(ID) {
	if ( confirm( "Vill du verkligen radera denna post med ID " + ID + "?" ) ) {
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
   }


/*
 * Colorpicker
 */
//jQuery(document).ready(function($){
//			$(".colorpicker").colorbox({
//				labels: [
//					'Black', 'Brown', 'Cardinal', 'Columbia', 'DkGreen', 'Gold', 'Gray', 
//					'Kelly', 'Maroon', 'Navy', 'Orange', 'Pink', 'Purple', 'Red', 'Royal', 'White'
//				],
//				colors: [
//					'000000', '502800', '500000', '1F2264', '003200', '646400', '4B4B4B', '005000', 
//					'320000', '000032', '643C00', '642864', '330032', '640000', '000064', 'fdfdfd'
//				],
//				width: 20,
//				height: 20,
//				top: 0,
//				left: 0,
//				perLine: 4,
//				onSelect: function($selected, color, index, label){
//					$("#RosterHomeJersey").val(index);
//				}
//			});
//			
//			
//			
//			$(".colorpicker").colorbox('setColorIndex', 1);
//		});
//jQuery(document).ready(function($){
//    $(".colorpicker").spectrum({
//        showPaletteOnly: true,
//        showPalette:true,
//        color: 'blanchedalmond',
//        palette: [
//            ['black', 'white', 'blanchedalmond',
//            'rgb(255, 128, 0);', 'hsv 100 70 50'],
//            ['red', 'yellow', 'green', 'blue', 'violet']
//        ],
//    change: function(color) {
//        $(".colorpicker").text(color.toHexString());
//    }        
//    });
//});
