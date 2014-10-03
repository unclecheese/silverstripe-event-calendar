<?php
class AnnouncementCalendarWidget extends Widget
{
	private static $has_one = array(
		'Calendar' => 'Calendar'
	);

	private static $db = array(
        'Title' => 'Varchar(255)',
		'AnnouncementCount' => 'Int'
	);

	private static $defaults = array(
		'AnnouncementCount' => 5,
        'Title' => 'Aankondigingen'		
	);

	
	public function Title()
	{
        return $this->Title;
	}
	
	public function CMSTitle()
	{
		return _t('AnnouncementCalendarWidget.CMSTITLE', 'Calendar Announcement Widget');
	}
	
	public function Description()
	{
		return _t('AnnouncementCalendarWidget.DESCRIPTION', 'Show a calendar widget with links to announcements.');
	}
	
	public function getCMSFields()
	{
		return new FieldList(
				new TextField('Title', _t('AnnouncementCalendarWidget.FIELD_TITLE', 'Title (optional)')),
				new DropdownField('CalendarID', _t('AnnouncementCalendarWidget.FIELD_CALENDAR', 'Calendar'), Calendar::get()->sort('DefaultDateHeader')->map('ID', 'Title')),
				new NumericField('AnnouncementCount', _t('AnnouncementCalendarWidget.FIELD_ANNOUNCEMENTCOUNT', 'Number off announcements'))
		);
	}
	
	public function getCurrentDate()
	{
		return self::get_current_date()->format('Y-m-d');
	}

	public function Events()
	{
		return $this->Calendar()->UpcomingEvents($this->AnnouncementCount, null);
	}
}

class AnnouncementCalendarWidget_Controller extends Widget_Controller
{
	public function Events()
	{
		return $this->Events(); 
	}
}