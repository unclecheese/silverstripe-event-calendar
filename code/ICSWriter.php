<?php
/**
 * A simple ICS writer.
 *
 * <h2>Examples</h2>
 * 
 * <h3>Send to client</h3>
 * 
 * <code>
 * $writer = new ICSWriter($this->data(), Director::absoluteURL('/'));
 * $writer->sendDownload();
 * </code>
 * 
 * <h3>Get output</h3>
 * 
 * <code>
 * $writer = new ICSWriter($this->data(), Director::absoluteURL('/'));
 * $writer->getOutput();
 * </code>
 * 
 * @todo Support recurring events
 * @copyright 2011 Dimension27
 * @author Alex Hayes <alex.hayes@dimension27.com>
 * @link https://github.com/dimension27/EventCalendar
 */
class ICSWriter
{

	/**
	 * @var Calendar
	 */
	public $calendar;

	public $host;
	public $prodid;
	public $limit;
	
	/**
	 * @var array
	 */
	protected $lines = array();
	
	/**
	 * Construct an ICSWriter instance.
	 * 
	 * @param Calendar $calendar The calendar to render.
	 * @param string $host       The calendar host.
	 * @param string $prodid     Specifies the identifier for the product that created the iCalendar object. If
	 *                           null then $host will be used in the generation of this.
	 * @param int $limit         Limit the amount of upcoming events to this number
	 *
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
    public function __construct( Calendar $calendar, $host, $prodid = null, $limit = 100 ) {
    	$this->calendar = $calendar;
    	$this->host = $host;
    	$this->prodid = $prodid;
    	$this->limit = $limit;
    }
    
    public function sendDownload() {
		header("Cache-Control: private");
		header("Content-Description: File Transfer");
		header("Content-Type: text/calendar");
		header("Content-Transfer-Encoding: binary");
		$filename = preg_replace("/[^a-zA-Z0-9s]/", "", $this->calendar->Title) . '.ics';
  		if(stristr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
 			header("Content-disposition: filename=" . $filename . "; attachment;");
  		} else {
 			header("Content-disposition: attachment; filename=" . $filename);
  		}
  		echo $this->getOutput();
    }
    
    /**
     * Get the calendar as a string.
     *
     * @author Alex Hayes <alex.hayes@dimension27.com>
     */
    public function getOutput() {
    	$this->lines = array();
    
		$this->addLine('BEGIN:VCALENDAR');
		$this->addLine('VERSION:2.0');
    	
		if( is_null($this->prodid) ) {
			$this->addLine("PRODID:" . '-//'.$this->host.'//NONSGML v1.0//EN');
		} 
		elseif( !is_null($this->prodid) ) {
			$this->addLine("PRODID:" . $this->prodid);
		}
		
    	$upcomingEvents = $this->calendar->UpcomingEvents($this->limit); /* @var $upcomingEvents DataObjectSet */
    	foreach($upcomingEvents as $dateTime) { /* @var $event CalendarDateTime */
    		$this->addDateTime($dateTime);
    	}
    	
		$this->addLine('END:VCALENDAR');
		
		return implode("\r\n", $this->lines);
    }
    
    /**
     * Add a line to the stack.
     * 
     * @param string $line
     * @return void
     *
     * @author Alex Hayes <alex.hayes@dimension27.com>
     */
    protected function addLine($line) {
    	$this->lines[] = $line;
    }
    
    /**
     * 
     * 
     * @param CalendarDateTime $dateTime
     * @return string
     *
     * @author Alex Hayes <alex.hayes@dimension27.com>
     */
    protected function getUID( CalendarDateTime $dateTime ) {
    	return $dateTime->ID.'@'.$this->host;
    }

    /**
     * Get an ical formatted datetime string.
     *
     * @param Date $date
     * @param Time $time
     * @return string
     *
     * @todo Add timezone support - note atm there is no timezone support in either Date or Time.
     * 
     * @author Alex Hayes <alex.hayes@dimension27.com>
     */
	protected function getFormatedDateTime( Date $date = null, Time $time = null ) {
		$timestamp = null;
		if($date && $time) {
			$timestamp = strtotime($date . ' ' . $time);
		}
		else {
			$timestamp = time();
		}
		return gmdate('Ymd\THis\Z', $timestamp);
	}
    
	/**
	 * Add a CalendarDateTime to the stack.
	 * 
	 * @param CalendarDateTime $dateTime
	 * @return void
	 *
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
    protected function addDateTime( CalendarDateTime $dateTime ) {
    	$this->addLine('BEGIN:VEVENT');
		$this->addLine('UID:' . $this->getUID($dateTime) );
		$this->addLine('DTSTAMP;TZID=' . Calendar::$timezone . ':' . $this->getFormatedDateTime());
		$this->addLine('DTSTART;TZID=' . Calendar::$timezone . ':' . $this->getFormatedDateTime($dateTime->StartDate, $dateTime->StartTime));
		$this->addLine('DTEND;TZID='   . Calendar::$timezone . ':' . $this->getFormatedDateTime($dateTime->StartDate, $dateTime->StartTime));
		$this->addLine('URL:' . Director::absoluteURL($dateTime->ICSLink()));
		$this->addLine('SUMMARY:CHARSET=UTF-8:' . $dateTime->Event()->Title);
		$this->addLine('END:VEVENT');
    }
    
}
