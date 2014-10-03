<?php

class CalendarAnnouncement extends CalendarDateTime {

	private static $db = array (
		'Title' => 'Varchar(255)',
		'Content' => 'Text'
	);

	private static $has_one = array (
		'Calendar' => 'Calendar'
	);

	public function getCMSFields() {
		$f = parent::getCMSFields();
		$f->insertBefore(new TextField('Title', _t('CalendarAnnouncement.TITLE','Title of announcement')), "StartDate");
		$f->insertBefore(new TextareaField('Content', _t('CalendarAnnouncement.CONTENT','Announcement content')), "StartDate");		
		$this->extend('updateCMSFields', $f);

		return $f;
	}

	public function summaryFields() {
		return array (
				'Title' => _t('CalendarAnnouncement.TITLE','Title of announcement'),
				'FormattedStartDate' => _t('Calendar.STARTDATE','Start date'),
				'FormattedEndDate' => _t('Calendar.ENDDATE','End date'),
				'FormattedStartTime' => _t('Calendar.STARTTIME','Start time'),
				'FormattedEndTime' => _t('Calendar.ENDTIME','End time'),
				'FormattedAllDay' => _t('Calendar.ALLDAY','All day'),
		);
	}
	
	public function getTitle() {
		return $this->getField('Title');
	}

	public function getContent() {
		return $this->getField('Content');
	}

}
