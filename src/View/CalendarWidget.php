<?php

namespace UncleCheese\EventCalendar\Views;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\View\Requirements;
use SilverStripe\View\ViewableData;
use UncleCheese\EventCalendar\Pages\Calendar;

class CalendarWidget extends ViewableData
{
	protected $calendar;

	protected $selectionStart;

	protected $selectionEnd;

	protected $options = [];

	public function __construct(Calendar $calendar)
	{
		$this->calendar = $calendar;
	}

	public function setOption($k, $v)
	{
		$this->options[$k] = $v;
	}
	
	public function getDataAttributes()
	{
		$attributes = "";
		$this->options['url'] = $this->calendar->Link();
		foreach ($this->options as $opt => $value) {
			$attributes .= sprintf('data-%s="%s" ', $opt, Convert::raw2att($value));
		}
		return $attributes;
	}

	public function setSelectionStart($date)
	{
		$this->selectionStart = $date;
		return $this;
	}

	public function setSelectionEnd($date)
	{
		$this->selectionEnd = $date;
		return $this;
	}

	public function forTemplate()
	{
		Requirements::javascript('silverstripe/admin:thirdparty/jquery/jquery.min.js');
		Requirements::javascript("unclecheese/silverstripe-event-calendar:client/js/calendar-widget.js");

		$localeFile = _t(Calendar::class.'.DATEJSFILE', 'unclecheese/silverstripe-event-calendar:client/dist/js/lang/calendar_en.js');
		Requirements::javascript($localeFile);

		Requirements::javascript("unclecheese/silverstripe-event-calendar:client/dist/js/calendar-widget-init.js");
		Requirements::css("unclecheese/silverstripe-event-calendar:client/dist/css/calendar-widget.css");
		
		return '<div class="calendar-widget" ' . $this->getDataAttributes() . '></div>';
	}
}
