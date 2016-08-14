<?php
	
//make array of dates from file
$file = file_get_contents(get_template_directory() . '/last-contact-dates.txt');
$rows = explode("\r", $file);
$dates = array();
foreach ($rows as $row) {
	$row = explode("\t", $row);
	$date = strtotime($row[4]);
	if ($date) $dates[trim($row[2])] = date('Y-m-d', $date);
}
//dd($dates);

//get array of groups from database
$groups = get_posts(array('post_type' => TSML_GROUP, 'numberposts' => -1));

$exceptions = array();

//loop through dates and if it matches the group, update post meta
foreach ($dates as $group_id => $date) {
	$date_id = false;
	
	//loop through and grab group_id
	foreach ($groups as $group) {
		$string = substr($group->post_title, -10 - strlen($group_id));
		$match = ' (Group #' . $group_id . ')';
		if ($string == $match) {
			$date_id = $group->ID;
		}
	}
	
	//if date then update else report 
	if ($date_id) {
		update_post_meta($date_id, 'last_contact', date('Y-m-d', $last_contact));
	} else {
		$exceptions[] = $group_id;
	}
}

//report exceptions
if (count($exceptions)) {
	echo 'could not find groups with these ids:<ul>';
	foreach ($exceptions as $exception)	 {
		echo '<li>' . $exception . '</li>';
	}
} else {
	echo 'all found!';
}