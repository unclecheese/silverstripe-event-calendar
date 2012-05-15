<?php

class CalendarAnnouncement extends CalendarDateTime {


	static $db = array (
		'Title' => 'Varchar(255)',
		'Content' => 'Text'
	);


	static $has_one = array (
		'Calendar' => 'Calendar'
	);


	public function getCMSFields() {
		$f = parent::getCMSFields();
		$f->insertBefore(new TextField('Title', _t('CalendarAnnouncement.TITLE','Title of announcement')), "StartDate");
		$f->insertBefore(new TextareaField('Content', _t('CalendarAnnouncement.CONTENT','Announcement content')), "StartDate");		
		$this->extend('updateCMSFields', $f);

		return $f;
	}


	public function getTitle() {
		return $this->getField('Title');
	}


	public function getContent() {
		return $this->getField('Content');
	}



}