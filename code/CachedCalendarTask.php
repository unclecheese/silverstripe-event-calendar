<?php


class CachedCalendarTask extends HourlyTask {

	public function process() {
		increase_time_limit_to(300);
		DB::query("DELETE FROM CachedCalendarEntry");
		$future_years = $this->config()->cache_future_years;
		foreach(Calendar::get() as $calendar) {
			echo "<h2>Caching calendar '$calendar->Title'</h2>\n";
			foreach($calendar->getAllCalendars() as $c) {
				foreach($c->AllChildren() as $event) {
					// All the dates of regular events
					if($event->Recursion) {
						echo "<h3>Creating recurring events for '$event->Title'</h3>\n";
						$i = 0;
						$dt = $event->DateTimes()->first();
						if(!$dt) continue;
						if($dt->EndDate) {
							$end_date = sfDate::getInstance($dt->EndDate);
						}
						else {
							$end_date = sfDate::getInstance()->addYear($future_years);
						}
						$start_date = sfDate::getInstance($dt->StartDate);
						$recursion = $event->getRecursionReader();
						while($start_date->get() <= $end_date->get()) {
							if($recursion->recursionHappensOn($start_date->get())) {
								$dt->StartDate = $start_date->format('Y-m-d');
								$cached = CachedCalendarEntry::create_from_datetime($dt, $calendar);
								$cached->EndDate = $cached->StartDate;
								$cached->write();
							}
							$start_date->addDay();
							$i++;
						}
						echo "<p>$i events created.</p>\n";
					}
					else {
						foreach($event->DateTimes() as $dt) {
							echo "<p>Adding dates for event '$event->Title'</p>\n";
							$cached = CachedCalendarEntry::create_from_datetime($dt, $calendar);
							$cached->write();
						}					
					}
				// Announcements								
				}
				foreach($c->Announcements() as $a) {
					echo "<p>Adding announcement $a->Title</p>\n";
					$cached = CachedCalendarEntry::create_from_announcement($a, $calendar);
					$cached->write();
				}
			}
		}
		echo "Done!";
	}
}