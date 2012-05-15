(function($) {
$(function() {
	$('.calendar-view-more').live("click",function(e) {
		e.preventDefault();
		$(this).addClass('loading');
		var $t = $(this);
		$.ajax({
			url: $t.attr('href'),
			success: function(data) {
				$t.remove();
				$('#events').append(data);
			}

		})
	});

});

})(jQuery);