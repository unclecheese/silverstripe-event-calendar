<?php

class ICSFeed extends DataObject
{
	static $db = array (
		'Title' => 'Varchar(100)',
		'URL' => 'Varchar(255)'
	);
	
	static $has_one = array (
		'Calendar' => 'Calendar'
	);
	
	public function getCMSFields_forPopup()
	{
		return new FieldSet(
			new TextField('Title',_t('ICSFeed.TITLEOFFEED','Title of feed')),
			new TextField('URL',_t('ICSFeed.URLLINK','URL'),'http://')
		);
	}
}


?>