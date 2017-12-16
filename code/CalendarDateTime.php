<?php

class CalendarDateTime extends DataObject {

	private static $has_one = array (
		'Event' => 'CalendarEvent'
	);

	public function Link() {
		return Controller::join_links($this->Event()->Link(),"?date=".$this->StartDate);
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
			->limit($this->Event()->Parent()->OtherDatesCount);
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

	public function getTitle() {
		return $this->Event()->Title;
	}

	public function getContent() {
		return $this->Event()->Content;
	}

}
