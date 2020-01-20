<?php

namespace UncleCheese\EventCalendar\Models;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use UncleCheese\EventCalendar\Pages\Calendar;

class ICSFeed extends DataObject
{
	private static $table_name = 'UncleCheese_ICSFeed';

	private static $db = [
		'Title' => 'Varchar(100)',
		'URL' => 'Varchar(255)'
	];

	private static $has_one = [
		'Calendar' => Calendar::class
	];

	public function getCMSFields()
	{
		$fields = FieldList::create(
			TextField::create('Title',_t('ICSFeed.TITLEOFFEED','Title of feed')),
			TextField::create('URL',_t('ICSFeed.URLLINK','URL'),'http://')
		);

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}
	
	public function summaryFields()
	{
		return [
			'Title' => _t(__CLASS__.'.TITLE', 'Title')
		];
	}	
}