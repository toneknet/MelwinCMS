$(document).ready(function() {
  // Add fields if needed
  // .div_frm_list
  $( "<div id=\"allowed_palette_fields\" class=\"allowed_button_fields\"></div>" ).insertAfter( "p.fld_db_palette" );
  $( "<div id=\"allowed_order_fields\" class=\"allowed_button_fields\"></div>" ).insertAfter( ".div_frm_list" );
  $( "label.fld_db_palette").append('<img class="reload_palette" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBkPSJNMTIgMGMtNi42MjcgMC0xMiA1LjM3My0xMiAxMnM1LjM3MyAxMiAxMiAxMiAxMi01LjM3MyAxMi0xMi01LjM3My0xMi0xMi0xMnptNSAyMGwtMS4zNTktMi4wMzhjLTEuMDYxLjY1My0yLjMwNSAxLjAzOC0zLjY0MSAxLjAzOC0zLjg1OSAwLTctMy4xNC03LTdoMmMwIDIuNzU3IDIuMjQzIDUgNSA1IC45MjcgMCAxLjc4Ni0uMjY0IDIuNTI3LS43MDhsLTEuNTI3LTIuMjkyaDUuNzE5bC0xLjcxOSA2em0wLThjMC0yLjc1Ny0yLjI0My01LTUtNS0uOTI3IDAtMS43ODYuMjY0LTIuNTI3LjcwOGwxLjUyNyAyLjI5MmgtNS43MTlsMS43MTktNiAxLjM1OSAyLjAzOGMxLjA2MS0uNjUzIDIuMzA1LTEuMDM4IDMuNjQxLTEuMDM4IDMuODU5IDAgNyAzLjE0IDcgN2gtMnoiLz48L3N2Zz4=">');

// $( "<div>xxx</div>" ).insertAfter( "#db_src" );




  $('.reload_palette').on('click',function(){
     getAddToButtons();
  });


  $("#db_label").on('change', function(){
    var pid = $('#db_pid').val() ?? null;
    if (pid == null || pid == 0) return;
    // console.log(pid);
    if (typeof pageLink === 'undefined' || pageLink == null || pid == 0) return;
    pageTmp = JSON.parse(pageLink);
    var pagename = $(this).val();
    pagename = pagename.toLowerCase();
    console.log(pagename);
    pagename = pagename
    .replace(/å/g, 'a')
    .replace(/ä/g, 'a')
    .replace(/ö/g, 'o');
    pagename = pagename.replace(/\s+/g, '_');
    pagename = pagename.replace(/[^a-z0-9_]/g, '');
    pagename = '/'+pageTmp[pid]+'/'+ pagename;
    pagename = pagename.replace(/_+/g, '_'); // Replace multiple underscores with a single one
    // pagename = pagename.replace(/^_/, ''); // Remove leading underscore
    pagename = pagename.replace(/_$/, ''); // Remove trailing underscore
    console.log(pagename);
    $("#db_src").val(pagename);
    // if ()
    // pageLink 
  })


  $('#db_pid').change(function(){
    var id = $(this).find(":selected").val();
    $.ajax({
      url: URLroot + "/system/ajax/ajaxRequest.php?action=menumaker&subaction=path&type=get&data=" + id,
      context: document.body
    }).done(function(data) {
      $('.fld_db_src').val(data).focus();
    });
  });



  $( "#db_table" ).change(function() {
    getAddToButtons();
  });

  function getAddToButtons() {
    var table = $( "#db_table" ).val();
    $.ajax({
      url: URLroot + "/system/ajax/ajaxRequest.php?action=menumaker&subaction=palette&type=get&data=" + table,
      context: document.body
    }).done(function(data) {
      $("#allowed_palette_fields").html(data);
      $("#allowed_order_fields").html(data);
      // Remove .addtopalette from order fields
      $("#allowed_order_fields span").removeClass('addtopalette');
      $("#allowed_order_fields span").addClass('addtoorder');
    });
  }

  // Fixar paletten
  $('body').on('click','.addtopalette',function(){
      var word = $(this).text();
      var textField = $('#db_palette');
      if (textField.val()=='') {
          textField.val(textField.val()+word);
      } else {
          textField.val(textField.val()+','+word);
      }
  });
  // Fixar listningens ordningsföljd
  $('body').on('click','.addtoorder',function(){
      var word = $(this).text();
      var textField = $('.mm_db_list_order');
      if (textField.val()=='') {
          textField.val(textField.val()+word);
      } else {
          textField.val(textField.val()+','+word);
      }
  });

});
