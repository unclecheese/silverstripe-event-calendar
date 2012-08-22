function navigateToDate(date)
{
	loc = new String(document.location);	
	query_string = loc.match(/\?.*/);
	if(query_string) 
		loc = loc.replace(query_string, "");
	else 
		query_string = "";
	parts = loc.split(current_url_segment);
	document.location = controller_url_segment+"view/"+date+query_string
}

function zeroPad(num) {
	var s = '0'+num;
	return s.substring(s.length-2)
}

