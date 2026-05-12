
// Test för att få ut vilka sidor man kan se/ändra med de behörigheter man har.
$(document).ready(function() {

  $( ".fld_db_groups" ).first().css( "position", "relative" ).after( "<div class=\"test_fixering\">Test</div>" );

  const testElements = document.getElementsByClassName('frm_groups');
  const testDivs = Array.prototype.filter.call(testElements, function(testElement){
    testElement.addEventListener("click",clickGroups);
    console.log(testElement.getAttribute("name"));
    //return testElement.nodeName === 'DIV';
  });

  function clickGroups(e) {
    //console.log("Click");
    var checkedVals = $('.fld_db_groups:checkbox:checked').map(function() {

      var sd = $(this).attr('name').replace(/[^0-9]/gi, ''); // Replace everything that is not a number with nothing
      var number = parseInt(sd, 10);
      return number;
    }).get();

    //alert(checkedVals.join(","));
    var groups = checkedVals.join(",");
    //console.log(groups);
    $('.test_fixering').load(URLroot + "system/ajax/ajaxRequest.php?action=getAccessTree&groups="+groups);
    // Get all fld_db_groups that are checked
  }

  clickGroups();

});
