<?php

class CalendarWidget extends ViewableData
{
	function __construct($controller, sfDate $start_date = null, $end_date = null, $default_view = false)
	{
		if($start_date === null) $start_date = new sfDate();
		if($end_date === null || !$end_date instanceof sfDate)
			$end_date = $start_date;
			
		$range_start = $default_view ? "null" : "'".$start_date->format('Y-m-d')."'";
		$range_end = $default_view ? "null" : "'".$end_date->format('Y-m-d')."'";
		$month_view = "'".($start_date->format('m')-1)."'";
		$year_view = "'".$start_date->format('Y')."'";
		
		Requirements::customScript("
			var controller_url_segment = '" . $controller->AbsoluteLink() . "'; 
			var current_url_segment = '" . Controller::curr()->Link() . "';
			var start_date = $range_start;	
			var end_date = $range_end; 
			var month_view = $month_view; 
			var year_view = $year_view;
		");
		
		$file = _t('CalendarWidget.LOCALEFILE','date_en.js');
		
		Requirements::javascript("event_calendar/javascript/locale/$file");
		
		
	 }
	 
	 public function forTemplate()
	 {
	 	return $this->renderWith('CalendarWidget');
	 }
}

class MonthNavigator extends ViewableData
{
	public function __construct($controller, sfDate $start_date = null)
	{
		if($start_date === null)
			$start_date = new sfDate();
	 	Requirements::customScript("
			var controller_url_segment = '" . $controller->Link() . "'; 
			var current_url_segment = '" . Controller::curr()->Link() . "';
		");

		$start = new sfDate($start_date->firstDayOfMonth()->dump());
		$end = new sfDate($start_date->firstDayOfMonth()->dump());

		$start->subtractMonth(6);
		$end->addMonth(6);

		$map = array();
		while($start->get() < $end->get()) {
			$map[$start->format('Y-m')] = CalendarUtil::i18n_date("%B %Y",$start->get());
			$start->addMonth();
		}
		unset($start, $end);

		$this->Dropdown = new DropdownField('MonthNavigator',null,$map, $start_date->reset()->format('Y-m'));
	}
	
	public function forTemplate()
	{
		return $this->renderWith('MonthNavigator');
	}

}


class LiveCalendarWidget extends ViewableData
{
  protected $calendar;
  protected $calendar_class;
  protected $start_date;
  protected $end_date;
  protected $default_view;
  protected $anchor_start;
  protected $anchor_end;
  protected $date_counter;
  protected $calendarHeader;
  protected $startDayOfMonth;
  protected $lastDateOfMonth;
  protected $rows;
  
  
  public function __construct(Calendar $calendar, $start_date = null, $end_date = null, $default_view = false)
  {
    $this->calendar = $calendar->hasMethod('getModel') ? $calendar->getModel() : $calendar;
    $this->calendar_class = $calendar->class;
    $this->default_view = $default_view;
    if(is_string($start_date)) $this->start_date = new sfDate($start_date);
    else if($start_date instanceof sfDate) $this->start_date = new sfDate($start_date->get());
    else $this->start_date = new sfDate();
    $this->date_counter = new sfDate($this->start_date->get());
    if(!Director::is_ajax())
      $this->anchor_start = new sfDate($this->start_date->get());
    
    if(is_string($end_date)) $this->end_date = new sfDate($end_date);
    else if($end_date instanceof sfDate) $this->end_date = $end_date;
    else $this->end_date = new sfDate();
    if(!Director::is_ajax())
      $this->anchor_end = new sfDate($this->end_date->get());
    
    $this->date_counter->firstDayOfMonth();
		$this->calendarHeader = CalendarUtil::i18n_date('%B',$this->date_counter->get()) . " " . $this->date_counter->retrieve(sfTime::YEAR);
		$this->startDayOfMonth = $this->date_counter->firstDayOfMonth()->format('w');
		$this->lastDateOfMonth = $this->date_counter->finalDayOfMonth()->format('j');
		$this->rows = ceil(($this->startDayOfMonth + $this->lastDateOfMonth)/7);
  }
  
  public function setAnchorStart($date)
  {
    $this->anchor_start = new sfDate(CalendarUtil::getDateFromString($date));
  }
  
  public function setAnchorEnd($date)
  {
    $this->anchor_end = new sfDate(CalendarUtil::getDateFromString($date));  
  }
  
  public function Link($action = null)
  {
    if($action === null) $action = "";
    return Director::baseURL()."LiveCalendarWidget_Controller"."/$action";
  }
  
  public function ShowMonthLink($month)
  {
    $default_view = $this->default_view ? "1" : "0";
    return $this->Link('show')."/".$month."/".$this->anchor_start->format('Ymd')."/".$this->anchor_end->format('Ymd')."/".$this->calendar->data()->class."/".$this->calendar->ID."/".$default_view;
  }
  
  protected function getQuickMonthLink()
  {
    $d = new sfDate();
	return $this->calendar->AbsoluteLink()."view/".$d->firstDayOfMonth()->format('Ym');
  }
  
  protected function getQuickWeekLink()
  {
  	$d = new sfDate();
	return $this->calendar->AbsoluteLink()."view/".$d->firstDayOfWeek()->format('Ymd')."/".$d->finalDayOfWeek()->format('Ymd');
  }
  
  protected function getQuickWeekendLink()
  {
  	$d = new sfDate();
		// Saturday? Dial back to Friday
  	if($d->format('w') == 6)
  		$d->previousDay();
  	// Before Friday? Advance. Otherwise, it's Friday, so leave it alone.
  	else if($d->format('w') < 5)
  		$d->nextDay(sfTime::FRIDAY);
  	
	return $this->calendar->AbsoluteLink()."view/".$d->format('Ymd')."/".$d->addDay(2)->format('Ymd');
  }
  
  
  protected function getEventsFor($start, $end)
  {
	  $events = $this->calendar->data()->Events(null, $start, $end);
    $map = array();
	  if($events) {
      foreach($events as $event) {
        if($event->EndDate && $event->EndDate != $event->StartDate) {
          $current_event = new sfDate($event->StartDate);
          while($current_event->date() != $event->EndDate) {
            $map[] = $current_event->date();
            $current_event->addDay();
          }
          $map[] = $event->EndDate;
        }
        else
          $map[] = $event->StartDate;
      }
	  }
	  return $map;  
  }
  

  protected function getWeeks()
  {
    $weeks = new DataObjectSet();
    $today = new sfDate();
    $today->clearTime();
    $this->date_counter->firstDayOfMonth()->firstDayOfWeek();
    $view_start = new sfDate($this->date_counter->get());
    $view_end = new sfDate($view_start->addDay($this->rows*7)->subtractDay()->get());
    $view_start->reset();
    $this->start_date->reset();
    $event_map = $this->getEventsFor($view_start, $view_end);
    
 		for($i=0; $i < $this->rows; $i++)
		{
		  $days = new DataObjectSet();
		  $week_range_start = $this->date_counter->format('Ymd');
			for($j=0; $j < 7; $j++)
			{
				$current_day = "";
				if(!$this->default_view) {
				  if( ($this->date_counter->get() >= $this->anchor_start->get()) && ($this->date_counter->get() <= $this->anchor_end->get()) )
				    $current_day = "currentDay";
        }				  
				$days->push(new ArrayData(array(
				  'Today'           => $this->date_counter->get() == $today->get() ? "calendarToday" : "",
				  'OutOfMonth'      => $this->date_counter->format('m') != $this->start_date->format('m') ? "calendarOutOfMonth" : "",
				  'CurrentDay'      => $current_day,
				  'HasEvent'        => in_array($this->date_counter->date(), $event_map) ? "hasEvent" : "",
				  'ShowDayLink' => $this->calendar->Link('view')."/".$this->date_counter->format('Ymd'),
				  'Number'          => $this->date_counter->format('d')
				)));
				$this->date_counter->addDay();
			}
			$week_range_end = $this->date_counter->subtractDay()->format('Ymd');
			$this->date_counter->addDay();
			$weeks->push(new ArrayData(array(
			 'Days' => $days,
			 'ShowWeekLink' => $this->calendar->Link('view')."/".$week_range_start."/".$week_range_end
			)));
		}
		return $weeks;  
  }
  
  protected function getNavigationOptions()
  {
    $options = new DataObjectSet();
    $counter = new sfDate($this->start_date->get());
    $counter->subtractMonth(6);
    for($i = 0;$i < 12;$i++) {
      $options->push(new ArrayData(array(
        'Link' => $this->ShowMonthLink($counter->format('Ym')),
        'Selected' => $this->start_date->format('Ym') == $counter->format('Ym') ? 'selected="selected"' : '',
        'Month' => CalendarUtil::i18n_date('%B, %Y',$counter->get())
      )));
      $counter->addMonth();
    }
    unset($counter);
    return $options;
  }
  
      
  public function forTemplate()
  {
		return $this->customise(array(
      'Weeks' => $this->getWeeks(),
      'NavigationOptions' => $this->getNavigationOptions(),
	  'CurrentMonthLink' => $this->calendar->Link('view')."/".$this->start_date->format('Y-m'),
      'PrevMonthLink'     => $this->ShowMonthLink($this->start_date->subtractMonth()->format('Ym')),
      'NextMonthLink'     => $this->ShowMonthLink($this->start_date->addMonth(2)->format('Ym')),
      'QuickMonthLink'    => $this->getQuickMonthLink(),
      'QuickWeekLink'     => $this->getQuickWeekLink(),
      'QuickWeekendLink'  => $this->getQuickWeekendLink(),
      'CalendarHeader'    => $this->calendarHeader,
      'Ajax'              => Director::is_ajax(),
      'Sun'               => CalendarUtil::i18n_date('%a',$this->date_counter->previousDay(sfTIME::SUNDAY)->get()),
      'Mon'               => CalendarUtil::i18n_date('%a',$this->date_counter->addDay()->get()),
      'Tue'               => CalendarUtil::i18n_date('%a',$this->date_counter->addDay()->get()),
      'Wed'               => CalendarUtil::i18n_date('%a',$this->date_counter->addDay()->get()),
      'Thu'               => CalendarUtil::i18n_date('%a',$this->date_counter->addDay()->get()),
      'Fri'               => CalendarUtil::i18n_date('%a',$this->date_counter->addDay()->get()),                        
      'Sat'               => CalendarUtil::i18n_date('%a',$this->date_counter->addDay()->get())      
		))->renderWith('LiveCalendarWidget');
  }
  
  
}

class LiveCalendarWidget_Controller extends Controller
{
  static $url_handlers = array (
    'show/$CurrentMonth/$AnchorStart/$AnchorEnd/$CalendarClass/$CalendarID/$DefaultView' => 'handleShow'
  );

  public function handleShow($request)
  {
    $calendar = DataObject::get_by_id($request->param('CalendarClass'),$request->param('CalendarID'));
    if($calendar) {
      $default_view = $request->param('DefaultView') == "1";
      $c = new LiveCalendarWidget(
        $calendar,
        new sfDate(CalendarUtil::getDateFromString($request->param('CurrentMonth'))),
        null,
        $default_view
      );
      $c->setAnchorStart($request->param('AnchorStart'));
      $c->setAnchorEnd($request->param('AnchorEnd'));
      echo $c->forTemplate();
    }
    else return false;
  }

}


?>