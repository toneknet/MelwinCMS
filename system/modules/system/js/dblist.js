$(document).ready(function() {
        $(".sortable tbody tr").on( "mouseenter", function() {
          $( this ).addClass( "hover" );
        } ).on( "mouseleave", function() {
          $( this ).removeClass( "hover" );
        } );
        $("table tbody").sortable({
            handle: '.handle',
            update: function (event,ui) {
              var dta = "";
              $(".sortable tbody tr").each(function(i) {
                  var id = $(this).attr('id');
                  dta+="&dblist%5B%5D=" + id;
              });
              var tablename = $( "#dbtable" ).text();
              $('#AjaxResult').load( URLroot + "/system/ajax/ajaxRequest.php?action=system&subaction=reorder&table=" + tablename + dta);
            },
        }).disableSelection();
});
