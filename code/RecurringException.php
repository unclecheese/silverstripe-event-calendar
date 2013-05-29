<?php

class RecurringException extends DataObject
{
	static $db = array (
		'ExceptionDate' => 'Date'
	);


	
	static $has_one = array (
		'CalendarEvent' => 'CalendarEvent'
	);


	static $default_sort = "ExceptionDate ASC";

        public function getCMSFields() {
                DateField::set_default_config('showcalendar', true);
                $f = new FieldList(
                        new DateField('ExceptionDate',_t('CalendarDateTime.EXCEPTIONDATE','Exception Date'))
                );

                $this->extend('updateCMSFields', $f);

                return $f;
        }

       public function summaryFields() {
                return array (
                        'FormattedExceptionDate' => _t('Calendar.EXCEPTIONDATE','Exception date')
                );
        }

        public function getFormattedExceptionDate() {
           if(!$this->ExceptionDate) return "--";
           return CalendarUtil::get_date_format() == "mdy" ? $this->obj('ExceptionDate')->Format('m-d-Y') : $this->obj('ExceptionDate')->Format('d-m-Y');
        }

}
