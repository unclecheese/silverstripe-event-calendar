<?php

class RecurringException extends DataObject {
	
	private static $db = array (
		'ExceptionDate' => 'Date'
	);
	
	private static $has_one = array (
		'CalendarEvent' => 'CalendarEvent'
	);


	private static $default_sort = "ExceptionDate ASC";
	

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


    public function canCreate($member = null) {
        return Permission::check("CMS_ACCESS_CMSMain");
    }
    
    public function canEdit($member = null) {
        return Permission::check("CMS_ACCESS_CMSMain");
    }
    
    public function canDelete($member = null) {
        return Permission::check("CMS_ACCESS_CMSMain");
    }
    
    public function canView($member = null) {
        return Permission::check("CMS_ACCESS_CMSMain");
    }
}
