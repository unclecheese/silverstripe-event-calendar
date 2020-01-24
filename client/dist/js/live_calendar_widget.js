(function($){
  $(function(){
    var refreshLink = function() {
      $('#live-calendar-widget').load($(this).attr('href'),bind);
      return false;
    }
    var refreshSelect = function() {
    	$t = $(this);
    	if($t.val().match('LiveCalendarWidget_Controller'))
	      $('#live-calendar-widget').load($t.val(),bind);
	    else
	    	document.location = $t.val();
    }
    var bind = function() {
      $('.month-nav').click(refreshLink);
      $('#live-calendar-widget-navigator').change(refreshSelect);
    }
    bind();
  });
})(jQuery);