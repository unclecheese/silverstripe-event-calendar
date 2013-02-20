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
				$('#event-calendar-events').append(data);
				fetching = false;
			}

		})
	});

	/*
	$(window).scroll(function() {
		if($('.calendar-view-more').length && !fetching) {
			var offset = $('.calendar-view-more').offset();
			if($(window).scrollTop()+$(window).height() >= offset.top) {				
				$('.calendar-view-more').click();
			}			
		}
	});
	*/
});

})(jQuery);