(function($) {
$(function() {

	var fetching = false;

	$('.calendar-view-more').live("click",function(e) {
		fetching = true;
		e.preventDefault();
		$(this).addClass('loading');
		var $t = $(this);
		$.ajax({
			url: $t.attr('href'),
			success: function(data) {
				$t.remove();
				$('#events').append(data);
				fetching = false;
			}

		})
	});


	$(window).scroll(function() {
		if ($(window).scrollTop() >= ($(document).height() - $(window).height())) {	
			if ($('.calendar-view-more').length && !fetching) {      	
				$('.calendar-view-more').click();
			}        
		}
	});
});

})(jQuery);