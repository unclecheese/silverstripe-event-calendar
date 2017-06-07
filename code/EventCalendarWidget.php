if (class_exists('Widget')) {

	class EventCalendarWidget extends Widget {

		private static $title = 'Widget for calendar page';
		private static $cmsTitle = "Widget for calendar page";
		private static $description = "Displays a calendar.";

		private static $has_one = array(
			'Calendar' => 'Calendar'
		);

		public function getCMSFields() {
			$self =& $this;

			$this->beforeUpdateCMSFields(function ($fields) use ($self) {
				$fields->merge(array(
					DropdownField::create('CalendarID', 'Calendar', Calendar::get()->map())
				));
			});

			return parent::getCMSFields();
		}

		public function getCalendarWidget() {
			return $this->Calendar()->CalendarWidget();
		}
	}
}
