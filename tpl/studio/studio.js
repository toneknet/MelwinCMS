/* Small enhancements only: navigation remains available without JavaScript. */
document.addEventListener('DOMContentLoaded', function () {
  var nav = document.getElementById('studio-navigation');
  if (nav) {
    nav.querySelectorAll('.dropdown > button').forEach(function (button, index) {
      var panel = button.nextElementSibling;
      if (!panel || !panel.classList.contains('dropdown-content')) return;
      panel.id = 'studio-submenu-' + index;
      button.setAttribute('aria-controls', panel.id);
      button.setAttribute('aria-expanded', 'true');
      button.addEventListener('click', function () {
        var expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
        panel.hidden = expanded;
      });
    });
  }
  // Preserve the synchronized year-calendar panes from the Skeleton template.
  if (window.jQuery) {
    var $ = window.jQuery;
    $('#cal_year_div_month').on('scroll', function () {
      $('#cal_year_div_day').scrollTop(this.scrollTop);
    });
    $('#cal_year_div_day').on('scroll', function () {
      $('#cal_year_div_month').scrollTop(this.scrollTop);
      $('#cal_year_div_weekday').scrollLeft(this.scrollLeft);
    });
    $('#cal_year_div_weekday').on('scroll', function () {
      $('#cal_year_div_day').scrollLeft(this.scrollLeft);
    });
  }
});
