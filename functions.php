<?php

add_action('pre_get_posts', function($wp_query){

	//ensure this filter isn't applied to the admin area
	if (is_admin()) return;

	if ($wp_query->get('page_id') == get_option('page_on_front')) {

		$wp_query->set('post_type', 'meetings');
		$wp_query->set('page_id', ''); //Empty

		//set properties that describe the page to reflect that we aren't really displaying a static page
		$wp_query->is_page = 0;
		$wp_query->is_singular = 0;
		$wp_query->is_post_type_archive = 1;
		$wp_query->is_archive = 1;

	}

});

//importer functions 

function format_cell($cell) {
	if ($cell == 'NULL') return '';
	return trim(str_replace('"', '', $cell));
}

function format_name($name) {
	$name = str_replace('.', ' . ', $name);
	$name = str_replace('-', ' - ', $name);
	$name = str_replace('(', ' ( ', $name);
	$name = str_replace(')', ' ) ', $name);
	$name = ucwords(strtolower($name));
	$words = explode(' ', $name);
	$count = count($words);
	$lower = array('a', 'and', 'at', 'de', 'for', 'in', 'la', 'los', 'of', 'on', 'or', 'the', 'to', 'with');
	$upper = array('AA', 'LGBT', 'PM', 'SOS');
	for ($i = 0; $i < $count; $i++) {
		if ($i && ($i != $count - 1) && in_array(strtolower($words[$i]), $lower)) {
			$words[$i] = strtolower($words[$i]);
		} elseif (in_array(strtoupper($words[$i]), $upper)) {
			$words[$i] = strtoupper($words[$i]);
		}
	}
	$name = implode(' ', $words);
	while (strstr($name, ' .')) $name = str_replace(' .', '.', $name);
	while (strstr($name, ' - ')) $name = str_replace(' - ', '-', $name);
	while (strstr($name, '( ')) $name = str_replace('( ', '(', $name);
	while (strstr($name, ' )')) $name = str_replace(' )', ')', $name);
	while (strstr($name, '  ')) $name = str_replace('  ', ' ', $name);
	return $name;
}

function format_location($name) {
	$name = format_name($name);
	return $name;
}

function format_region($region) {
	$regions = array(
		'BX' => 'Bronx',
		'BK' => 'Brooklyn',
		'C' => 'Connecticut',
		'M' => 'Manhattan',
		'N' => 'Nassau County',
		'NJ' => 'New Jersey',
		'O' => 'Orange County',
		'PD' => 'Putnm/Dutchess Counties',
		'Q'	=> 'Queens',
		'R' => 'Rockland County',
		'SF' => 'Suffolk County',
		'SI' => 'Staten Island',
		'SGU' => 'Sullivan/Green/Ulster Counties',
		'W' => 'Westchester County',
	);
	if (!array_key_exists($region, $regions)) die('Error: region "' . $region . '" was not found!');
	return $regions[$region];
}

function format_state($state, $region) {
	if (empty($state)) {
		if ($region == 'CT') return 'CT';
		if ($region == 'NJ') return 'NJ';
		return 'NY';
	}
	$states = array('CT', 'NJ', 'NY');
	if (!in_array($state, $states)) die('Error: state "' . $state . '" was not found!');
	return $state;
}

function format_day($day) {
	$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	if (!in_array($day, $days)) die('Error: day "' . $day . '" was not found!');
	return $day;
}

function format_notes($notes) {
	if ($pos = strpos($notes, 'Notes:')) {
		$notes = substr($notes, $pos + 6);
	}
	return $notes;
}