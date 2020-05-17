<?php

namespace UncleCheese\EventCalendar\Helpers;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

class iCal
{
    use Configurable;
    use Injectable;

    private $ics_files = [];

    public function iCal($ics_files)
    {
        $this->ics_files = $ics_files;
    } 
     
    public function iCalList()
    {
        return array_filter($this->ics_files, [$this, "iCalClean"]); 
    } 
     
    public function iCalClean($file)
    {
        return strpos($file, '.ics'); 
    } 
     
    public function iCalReader()
    {
    	$iCaltoArray = [];
        $array = $this->iCalList(); 
        foreach ($array as $icalfile) { 
            $iCaltoArray[$icalfile] = $this->iCalDecoder($icalfile); 
        } 
        return $iCaltoArray; 
    }
     
    public function iCalDecoder($file)
    {
        $ical = file_get_contents($file); 
        // unfold long lines http://tools.ietf.org/html/rfc5545#section-3.1
        $ical = str_replace("\r\n ", "", $ical);
        preg_match_all('/(BEGIN:VEVENT.*?END:VEVENT)/si', $ical, $result, PREG_PATTERN_ORDER); 
        for ($i = 0; $i < count($result[0]); $i++) { 
            $tmpbyline = explode("\r\n", $result[0][$i]); 
            foreach ($tmpbyline as $item) { 
                $tmpholderarray = explode(":",$item, 2); //value can contain ":", so not strip URLs for example
                if (count($tmpholderarray) > 1) {  
                    $majorarray[$tmpholderarray[0]] = $tmpholderarray[1]; 
                }
            } 
            foreach (["DESCRIPTION", "SUMMARY", "LOCATION"] as $key) {
                $majorarray[$key] = str_replace(
                    ['\\\\', '\\,', '\\;', '\\n', '\\N'], 
                    ['\\',   ',',   ';',   "\n",  "\n"], 
                    $majorarray[$key]
                );
            }
            $icalarray[] = $majorarray; 
            unset($majorarray); 
        }
        return $icalarray; 
    }
}
