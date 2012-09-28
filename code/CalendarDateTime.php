<?php

class CalendarDateTime extends DataObject
{
	

	 static $db = array (		
		'StartDate' => 'Date',
		'StartTime' => 'Time',
		'EndDate' => 'Date',
		'EndTime' => 'Time',
		'AllDay' => 'Boolean'		
	);
	

	static $has_one = array (
		'Event' => 'CalendarEvent'
	);


	static $date_format_override;


	static $time_format_override;


	static $default_sort = "StartDate ASC, StartTime ASC";


	static $offset = "0:00";


	public function getCMSFields() {
		DateField::set_default_config('showcalendar', true);
		$f = new FieldList(
			new DateField('StartDate',_t('CalendarDateTime.STARTDATE','Start date')),
			new DateField('EndDate',_t('CalendarDateTime.ENDDATE','End date')),
			new TimeField('StartTime', _t('CalendarDateTime.STARTTIME','Start time')),
			new TimeField('EndTime', _t('CalendarDateTime.ENDTIME','End time')),
			new CheckboxField('AllDay', _t('CalendarDateTime.ALLDAY','This event lasts all day'))
		);

		$this->extend('updateCMSFields', $f);

		return $f;
	}



	public function summaryFields() {
		return array (
			'FormattedStartDate' => _t('Calendar.STARTDATE','Start date'),
			'FormattedEndDate' => _t('Calendar.ENDDATE','End date'),
			'FormattedStartTime' => _t('Calendar.STARTTIME','Start time'),
			'FormattedEndTime' => _t('Calendar.ENDTIME','End time'),
			'FormattedAllDay' => _t('Calendar.ALLDAY','All day'),
		);
	}


	public function Link() {
		return Controller::join_links($this->Event()->Link(),"?date=".$this->StartDate);
	}



	public function DateRange() {		
		list($strStartDate,$strEndDate) = CalendarUtil::get_date_string($this->StartDate,$this->EndDate);		
		$html =   "<span class='dtstart' title='".$this->MicroformatStart()."'>" . $strStartDate . "</span>"; 
		$html .=	($strEndDate != "") ? "-" : "";
		$html .= "<span class='dtend' title='" .$this->MicroformatEnd() ."'>";
		$html .=    ($strEndDate != "") ? $strEndDate : "";
		$html .=  "</span>";
		
		return $html;
	}
	


	public function TimeRange() {
		$func = CalendarUtil::get_time_format() == "24" ? "Nice24" : "Nice";
		$ret = $this->obj('StartTime')->$func();
		$ret .= $this->EndTime ? " &mdash; " . $this->obj('EndTime')->$func() : "";
		return $ret;
	}


	public function Announcement() {
		return $this->ClassName == "CalendarAnnouncement";
	}



	public function OtherDates() {
		if($this->Announcement()) {
			return false;
		}
		
		if($this->Event()->Recursion) {	
			return $this->Event()->Parent()->getNextRecurringEvents($this->Event(), $this);
		}
		
		return DataList::create($this->class)
			->where("EventID = {$this->EventID}")
			->where("StartDate != '{$this->StartDate}'")
			->limit($this->Event()->Parent()->DefaultEventDisplay);
	}




	public function MicroformatStart($offset = true) {
		if(!$this->StartDate)
			return "";
			
		$date = $this->StartDate;
	
		if($this->AllDay)
			$time = "00:00:00";
		else
			$time = $this->StartTime ? $this->StartTime : "00:00:00";
	
		return CalendarUtil::microformat($date, $time, self::$offset);
	}
	


	public function MicroformatEnd($offset = true) {
		if($this->AllDay && $this->StartDate) {
			$time = "00:00:00";
			$end = sfDate::getInstance($this->StartDate);
			$date = $end->tomorrow()->date();
			unset($end);
		}
		else {
			$date = $this->EndDate ? $this->EndDate : $this->StartDate;
			$time = $this->EndTime && $this->StartTime ? $this->EndTime : (!$this->EndTime && $this->StartTime ? $this->StartTime : "00:00:00");
		}

		return CalendarUtil::microformat($date, $time, self::$offset);
	}



	public function ICSLink() {
		$ics_start = $this->obj('StartDate')->Format('Ymd')."T".$this->obj('StartTime')->Format('His');
		if($this->EndDate) {
			$ics_end = $this->obj('EndDate')->Format('Ymd')."T".$this->obj('EndTime')->Format('His'); 
		}
		else {
			$ics_end = $ics_start;
		}
		if($this->Feed) {
			return Controller::join_links(
				$this->Calendar()->Link(),
				"ics",
				$this->ID,
				$ics_start . "-" . $ics_end,
				"?title=".urlencode($this->Title)
			);
		}
		else if($this->Announcement()) {
			return Controller::join_links(
				$this->Calendar()->Link(),
				"ics","announcement-".$this->ID, 
				$ics_start . "-" . $ics_end
			); 
		}
		return Controller::join_links(
			$this->Event()->Parent()->Link(),
			"ics",
			$this->Event()->ID,
			$ics_start . "-" . $ics_end
		);
	}




	public function getFormattedStartDate() {
	   if(!$this->StartDate) return "--";
	   return CalendarUtil::get_date_format() == "mdy" ? $this->obj('StartDate')->Format('m-d-Y') : $this->obj('StartDate')->Format('d-m-Y');
	}
	


	public function getFormattedEndDate() {
	   if(!$this->EndDate) return "--";
	   return CalendarUtil::get_date_format() == "mdy" ? $this->obj('EndDate')->Format('m-d-Y') : $this->obj('EndDate')->Format('d-m-Y');
	}
		


	public function getFormattedStartTime() {
	   if(!$this->StartTime) return "--";
	   return CalendarUtil::get_time_format() == "12" ? $this->obj('StartTime')->Nice() : $this->obj('StartTime')->Nice24();
	}
	


	public function getFormattedEndTime() {
	   if(!$this->EndTime) return "--";
	   return CalendarUtil::get_time_format() == "12" ? $this->obj('EndTime')->Nice() : $this->obj('EndTime')->Nice24();
	}
	


	public function getFormattedAllDay() {
	   return $this->AllDay == 1 ? _t('YES','Yes') : _t('NO','No');
	}
	


	public function getTitle() {
		return $this->Event()->Title;
	}


	public function getContent() {
		return $this->Event()->Content;
	}



	public function getAllDatesInRange() {
		$start = sfDate::getInstance($this->StartDate);
		$end = sfDate::getInstance($this->EndDate);
		$dates = array ();
		while($start->get() <= $end->get()) {
			$dates[] = $start->format('Y-m-d');
			$start->tomorrow();
		}
		return $dates;
	}
	
	
	public function canCreate($member = null) { return Permission::check("CMS_ACCESS_CMSMain"); }

	public function canEdit($member = null) { return Permission::check("CMS_ACCESS_CMSMain"); }

	public function canDelete($member = null) { return Permission::check("CMS_ACCESS_CMSMain"); }

	public function canView($member = null) { return Permission::check("CMS_ACCESS_CMSMain"); }
	



}