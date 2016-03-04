<?php

//make the home page the meetings post_type archive
add_action('pre_get_posts', function($wp_query){
	if (is_admin()) return; //don't do this to inside pages
	if ($wp_query->get('page_id') == get_option('page_on_front')) {
		$wp_query->set('post_type', 'meetings');
		$wp_query->set('page_id', '');
		$wp_query->is_page = 0;
		$wp_query->is_singular = 0;
		$wp_query->is_post_type_archive = 1;
		$wp_query->is_archive = 1;
	}
});

//importer functions 

function format_cell($cell) {
	if ($cell == 'NULL') return '';
	$cell = str_replace('*', '', str_replace('"', '', str_replace('.', '', $cell)));
	return trim($cell);
}

function format_date($date) {
	return date('Y-m-d', strtotime($date));
}

function format_day($day) {
	$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	if (!in_array($day, $days)) die('Error: day "' . $day . '" was not found!');
	return $day;
}

function title_case($string) {
	$string = str_replace('.', ' . ', $string);
	$string = str_replace('-', ' - ', $string);
	$string = str_replace('/', ' / ', $string);
	$string = str_replace('(', ' ( ', $string);
	$string = ucwords(strtolower($string));
	$words = explode(' ', $string);
	$count = count($words);
	$lower = array('a', 'and', 'at', 'but', 'by', 'de', 'for', 'in', 'la', 'los', 'nor', 'of', 'on', 'or', 'the', 'to', 'with');
	$upper = array('AA', 'LGBT', 'NY', 'NYC', 'NYU', 'PM', 'SOS', 'YMCA', 'YWCA');
	for ($i = 0; $i < $count; $i++) {
		if ($i && ($i != $count - 1) && in_array(strtolower($words[$i]), $lower)) {
			$words[$i] = strtolower($words[$i]);
		} elseif (in_array(strtoupper($words[$i]), $upper)) {
			$words[$i] = strtoupper($words[$i]);
		}
	}
	$string = implode(' ', $words);
	while (strstr($string, ' .'))  $string = str_replace(' .',  '.', $string);
	while (strstr($string, ' -')) $string = str_replace(' -', '-', $string);
	while (strstr($string, '- ')) $string = str_replace('- ', '-', $string);
	while (strstr($string, ' /')) $string = str_replace(' /', '/', $string);
	while (strstr($string, '/ ')) $string = str_replace('/ ', '/', $string);
	while (strstr($string, '( '))  $string = str_replace('( ',  '(', $string);
	while (strstr($string, '  '))  $string = str_replace('  ',  ' ', $string);
	if ($string == 'Sage') return 'S.A.G.E.';
	if ($string == 'Home') return 'H.O.P.E.';
	if ($string == 'Byoc') return 'B.Y.O.C.';
	if ($string == 'Now-No Other Way') return 'N.O.W.-No Other Way';
	if ($string == 'How Club') return 'H.O.W. Club';
	
	return $string;
}

function format_name($name) {
	if ($pos = strpos($name, '(')) $name = substr($name, 0, $pos);
	$name = title_case($name);
	return $name;
}

function format_location($location) {
	$location = title_case($location);
	return $location;
}

function format_address(&$row) {
	$row['address'] = title_case($row['address']);
	
	//move everything after the comma to the notes
	if ($pos = strpos($row['address'], '(')) {
		$row['notes'] = str_replace(')', '', substr($row['address'], $pos)) . '<br>' . $row['notes'];
		$row['address'] = substr($row['address'], 0, $pos);
	}
	
	@list($row['address'], $after) = explode(',', $row['address'], 2);
	if ($after) $row['notes'] = $after . '<br>' . $row['notes'];
	
	//remove everything after @
	@list($row['address'], $after) = explode('@', $row['address'], 2);

	//remove everything after &
	@list($row['address'], $after) = explode('&', $row['address'], 2);

	$row['address']	= trim($row['address']);
	
	if (empty($row['address'])) die(implode(', ', $row));
}

function format_city($row) {
	extract($row);
	$cities = array('Hoboken', 'Yonkers', 'Saltaire', 'Kingston', 'Orangeburg', 'Mt Vernon', 'Callicoon',
		'White Plains', 'New Rochelle', 'Ocean Beach', 'Harrison', 'Scarsdale', 'Middletown', 'Wesley Hills',
		'Yorktown Heights', 'Brewster', 'Bellmore', 'Port Chester', 'Norwalk', 'Monroe', 'Stony Point', 
		'Larchmont', 'Purchase', 'Poughkeepsie', 'Katonah', 'Patchogue', 'Warwick', 'Newburgh', 'Dobbs Ferry',
		'Ferndale', 'Pearl River', 'Montauk', 'Shelter Island', 'Montrose', 'Valley Cottage', 'Sag Harbor',
		'Nyack', 'Blairstown', 'Harriman', 'Tarrytown', 'Port Washington', 'Cornwall', 'Mahopac', 'Babylon',
		'New Windsor', 'Cold Spring', 'Wappingers Falls', 'Briarcliff Manor', 'Pelham', 'Peekskill', 'Airmont',
		'Kent', 'Melville', 'Hewlett', 'Mount Sinai', 'Port Jervis', 'Carmel', 'Greenwich', 'Fire Island',
		'Southampton', 'Bedford', 'Somers', 'Highland Mills', 'Salisbury', 'Pleasantville', );
	if (empty($city)) {
		if ($region == 'Bronx') return 'Bronx';
		if ($region == 'Brooklyn') return 'Brooklyn';
		if ($region == 'Manhattan') return 'New York';
		if ($region == 'Queens') return 'Queens';
		if ($region == 'Staten Island') return 'Staten Island';
		foreach ($cities as $city) {
			$string = implode('|', compact('name', 'location', 'address', 'region'));
			if (stristr($string, $city)) {
				return $city;
			}
		}
		die('Error: city not found for row ' . $string);
	} elseif ($city == 'NY') {
		return 'New York';
	}
	return $city;
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

function format_region($region) {
	$regions = array(
		'BX' => 'Bronx',
		'BK' => 'Brooklyn',
		'C' => 'Connecticut',
		'M' => 'Manhattan',
		'N' => 'Nassau County',
		'NJ' => 'New Jersey',
		'O' => 'Orange County',
		'PD' => 'Putnam and Dutchess Counties',
		'Q'	=> 'Queens',
		'R' => 'Rockland County',
		'SF' => 'Suffolk County',
		'SI' => 'Staten Island',
		'SGU' => 'Sullivan, Green, and Ulster Counties',
		'W' => 'Westchester County',
	);
	if (!array_key_exists($region, $regions)) die('Error: region "' . $region . '" was not found!');
	return $regions[$region];
}

function format_subregion($row, $subregions) {
	if (!in_array($row['region'], array('M', 'BX', 'SI', 'Q', 'BK'))) return null;
	if (array_key_exists($row['postal_code'], $subregions)) return $subregions[$row['postal_code']];
	die('Error: subregion for "' . $row['postal_code'] . '" was not found!');
}

function format_postal_code($row) {
	if (is_numeric($row['postal_code']) && (strlen($row['postal_code']) == 5)) return $row['postal_code'];
	dd($row); 
	die('Error: postal_code "' . $row['postal_code'] . '" invalid!');
}

function format_types($types) {
	$translations = array(
		'AA Grapevine Literature' => 'Grapevine',
		'AA Literature' => 'Literature',
		'Agnostic' => 'Atheist / Agnostic',
		'As Bill Sees It' => 'Literature',
		'B = Beginners meeting' => 'Beginner',
		'BB = Big Book meeting' => 'Big Book',
		'Beginners Workshop' => 'Beginner',
		'Big Book Topic' => 'Big Book',
		'Big Book Workshop' => 'Big Book',
		'C = Closed Discussion meeting' => 'Closed',
		'Came To Believe' => 'Literature',
		//Children Welcome
		'Daily Reflections' => 'Literature',
		'Eleventh Step' => 'Step Meeting',
		'Eleventh Step Meditation' => 'Step Meeting',
		'First Step Workshop' => 'Step Meeting',
		'Fourth Step Workshop' => 'Step Meeting',
		'Gay Men' => 'Gay',
		'Gay, Lesbian and Bisexual' => 'LGBTQ',
		//H.I.V Positive
		'Interpreted for the Deaf' => 'Sign Language',
		'Lesbian' => 'Lesbian',
		'Living Sober' => 'Literature',
		//Medication
		'Meditation' => 'Meditation',
		'Meditation at Meeting' => 'Meditation',
		'Men' => 'Men Only',
		//Mental Health Issues in Sobriety
		'O = Open meeting' => 'Open',
		//OD = Open Discussion meeting
		//Polish Speaking
		//Promises
		'Rotating Step' => 'Step Meeting',
		//Round-Robin Meeting Format
		//Russian Speaking
		'S = Step meeting' => 'Step Meeting',
		'Sp = Spanish speaking group' => 'Spanish',
		'Spanish Speaking' => 'Spanish',
		//Special Purpose Groups
		//Spiritual Workshop
		//Sponsorship Workshop
		'Steps 1-2-3' => 'Step Meeting',
		'T = Tradition meeting' => 'Tradition',
		'Third Step' => 'Step Meeting',
		'Topic' => 'Topic Discussion',
		'Twelve Steps' => 'Step Meeting',
		'Twelve Traditions' => 'Tradition',
		//Under Six Months Sober
		'WC' => 'Wheelchair Accessible',
		'Women' => 'Women Only',
		'Young People' => 'Young People',
	);
	$return = array();
	$types = array_map('trim', explode('<br>', $types));
	foreach ($types as $type) {
		if (array_key_exists($type, $translations)) {
			$return[] = $translations[$type];
		}
	}
	return implode(', ', array_unique($return));
}

//for guide page
function format_time($string) {
	if ($string == '12:00') return '12N';
	if ($string == '23:59') return '12M';
	list($hours, $minutes) = explode(':', $string);
	if ($hours == 0) return '12:' . $minutes . 'a';
	if ($hours < 12) return $string . 'a';
	if ($hours > 12) $hours -= 12;
	return $hours . ':' . $minutes;
}