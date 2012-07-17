(function($) {
$(function() {

	$('#DefaultView select').live("change",function() {
		$('#DefaultFutureMonths').hide();
		if($(this).val() == "upcoming") {
			$('#DefaultFutureMonths').show();
		}
	}).change();


	$('#Recursion').entwine({
		onmatch: function() {


		var $tab = this.closest('.tab');
		var $recursion = this;
		var $customRecursionType = $tab.find('#CustomRecursionType').hide();
		var $dailyInterval = $tab.find('.dailyinterval').hide();
		var $weeklyInterval = $tab.find('.weeklyinterval').hide();
		var $monthlyInterval = $tab.find('.monthlyinterval').hide();
		var $monthlyIndex = $tab.find('.monthlyindex').hide();
		var $recurringDaysOfWeek = $tab.find('#RecurringDaysOfWeek').hide();
		var $recurringDaysOfMonth = $tab.find('#RecurringDaysOfMonth').hide();
		var $monthlyRecursionType1 = $tab.find('#MonthlyRecursionType1').hide();
		var $monthlyRecursionType2 = $tab.find('#MonthlyRecursionType2').hide();

		var resetPanels = function () {
			$dailyInterval.hide();
			$weeklyInterval.hide();
			$monthlyInterval.hide();
			$recurringDaysOfWeek.hide();
			$recurringDaysOfMonth.hide().find(':checkbox').attr('disabled', true);
			$monthlyRecursionType1.hide();
			$monthlyRecursionType2.hide();
			$monthlyIndex.hide().find('select').attr('disabled', true);			
		}

		$recursion.find('input').change(function() {			
			if($(this).is(':checked')) {
				$customRecursionType.show();				
			}
			else {
				$tab.find(':checkbox, :radio').attr('checked', false);
				$customRecursionType.hide();
				resetPanels();
			}
		}).change();

		$customRecursionType.find('input').change(function() {			
			if($(this).is(':checked')) {
				resetPanels();
				switch($(this).val()) {
					case "1":
						$dailyInterval.show();
					break;

					case "2":
						$weeklyInterval.show();
						$recurringDaysOfWeek.show();
					break;

					case "3":
						$monthlyInterval.show();
						$recurringDaysOfMonth.show();
						$monthlyRecursionType1.show();
						$monthlyRecursionType2.show();
						$monthlyIndex.show();						
					break;
				}
			}			
		}).change();

		$monthlyRecursionType1.find('input').change(function() {
			if($(this).is(':checked')) {
				$recurringDaysOfMonth.find(':checkbox').attr('disabled', false);
				$monthlyIndex.find('select').attr('disabled', true);
				$monthlyRecursionType2.find('input').attr('checked', false).change();
			}
		}).change();

		$monthlyRecursionType2.find('input').change(function() {
			if($(this).is(':checked')) {
				$recurringDaysOfMonth.find(':checkbox').attr('disabled', true);
				$monthlyIndex.find('select').attr('disabled', false);
				$monthlyRecursionType1.find('input').attr('checked', false).change();
			}
		}).change();

	

	}});






});
})(jQuery);