<?php

/**
 * Plugin Name: NY Intergroup PDF Generator
 */

//generates form from shortcode
include('form.php');

//generates pdf with ajax hook
include('pdf.php');

//for guide page
function format_time($string) {
	if ($string == '12:00') return '12N';
	if ($string == '23:59') return '12M';
	list($hours, $minutes) = explode(':', $string);
	$hours -= 0;
	if ($hours == 0) return '12:' . $minutes . 'a';
	if ($hours < 12) return $hours . ':' . $minutes . 'a';
	if ($hours > 12) $hours -= 12;
	return $hours . ':' . $minutes;
}

//need this for formatting the meeting types
function decode_types($type) {
	global $tsml_programs, $tsml_program;
	if (!array_key_exists($type, $tsml_programs[$tsml_program]['types'])) return '';
	return $tsml_programs[$tsml_program]['types'][$type];
}

//pdf function to get data and attach it to the regions array
function attachPdfMeetingData($regions) {
	global $symbols;
		
	//going to be checking this over and over
	$count_symbols = count($symbols);
	
	//get all the sub-regions and their children
	$sub_region_ids = get_terms(array(
		'taxonomy' => 'tsml_region',
		'exclude' => array_keys($regions),
		'fields' => 'ids',
	));
	
	$sub_sub_regions = array();
	
	foreach ($sub_region_ids as $sub_region_id) {
		$sub_sub_region_ids = get_terms(array(
			'taxonomy' => 'tsml_region',
			'parent' => $sub_region_id,
			'fields' => 'ids',
		));
		foreach ($sub_sub_region_ids as $sub_sub_region_id) {
			$sub_sub_regions[$sub_sub_region_id] = $sub_region_id;
		}
	}
		
	//build an array of table rows for each region all in one shot, to preserve memory
	$rows = array();
	$meetings = tsml_get_meetings();
	foreach ($meetings as $meeting) {
		
		//we group meetings by group-at-location
		$key = @$meeting['group_id'] . '-' . @$meeting['location_id'];
		
		//replace with parent category
		if (array_key_exists($meeting['region_id'], $sub_sub_regions)) {
			$meeting['region_id'] = $sub_sub_regions[$meeting['region_id']];
		}
		
		//make sure array region exists
		if (!array_key_exists($meeting['region_id'], $rows)) {
			$rows[$meeting['region_id']] = array();
		}
		
		//attach meeting to region
		if (!array_key_exists($key, $rows[$meeting['region_id']])) {
			$parts = explode(', ', $meeting['formatted_address']);
			$rows[$meeting['region_id']][$key] = array(
				'group' => @$meeting['group'],
				'location' => @$meeting['location'],
				'address' => $parts[0],
				'postal_code' => substr($parts[2], 3),
				'notes' => @$meeting['location_notes'],
				'last_contact' => empty($meeting['last_contact']) ? null : date('n/j/y', strtotime($meeting['last_contact'])),
				'wheelchair' => false,
				'spanish' => true,
				'days' => array(
					0 => array(),
					1 => array(),
					2 => array(),
					3 => array(),
					4 => array(),
					5 => array(),
					6 => array(),
				),
				'footnotes' => array(),
				'types' => array(), //for indexes
			);
		}
			
		//for indexes
		$rows[$meeting['region_id']][$key]['types'] = array_merge($rows[$meeting['region_id']][$key]['types'], $meeting['types']);
		
		//at least one meeting tagged wheelchair-accessible
		if (($index = array_search('X', $meeting['types'])) !== false) {
			$rows[$meeting['region_id']][$key]['wheelchair'] = true;
			unset($meeting['types'][$index]);
		}
		
		//at least one meeting *not* tagged spanish means row is not "spanish"
		if (!in_array('S', $meeting['types'])) $rows[$meeting['region_id']][$key]['spanish'] = false;
		
		//insert into day
		$time = ''; 
		if (($index = array_search('D',  $meeting['types'])) !== false) {
			$time .= 'OD-'; //open discussion meeting (comes before open because all ODs are open)
			unset($meeting['types'][$index]);
		} elseif (($index = array_search('O',  $meeting['types'])) !== false) {
			$time .= 'O-';  //open meeting
			unset($meeting['types'][$index]);
		} elseif (($index = array_search('BE', $meeting['types'])) !== false) {
			$time .= 'B-';  //beginners meeting
			unset($meeting['types'][$index]);
		} elseif (($index = array_search('B',  $meeting['types'])) !== false) {
			$time .= 'BB-'; //big book meeting
			unset($meeting['types'][$index]);
		} elseif (($index = array_search('ST', $meeting['types'])) !== false) {
			$time .= 'S-';  //step meeting
			unset($meeting['types'][$index]);
		} elseif (($index = array_search('TR', $meeting['types'])) !== false) {
			$time .= 'T-';  //tradition meeting
			unset($meeting['types'][$index]);
		} elseif (($index = array_search('C',  $meeting['types'])) !== false) {
			$time .= 'C-';  //closed meeting
			unset($meeting['types'][$index]);
		}
	
		$time .= format_time($meeting['time']);
	
		//per Janet, don't need Closed meeting type now because it's implied
		if (($index = array_search('C', $meeting['types'])) !== false) {
			unset($meeting['types'][$index]);
		}
			
		//append footnote to array
		if (!empty($meeting['types']) || !empty($meeting['notes'])) {
			//decide what this meeting's footnote should be
			$footnote = array_map('decode_types', $meeting['types']);
			if (!empty($meeting['notes'])) $footnote[] = $meeting['notes'];
			$footnote = implode(', ', $footnote);
			
			//add footnote if not full
			$count_footnotes = count($rows[$meeting['region_id']][$key]['footnotes']);
			//if (!is_array($rows[$meeting['region_id']][$key]['footnotes'])) dd($meeting);
			if (array_key_exists($footnote, $rows[$meeting['region_id']][$key]['footnotes'])) {
				$index = array_search($footnote, $rows[$meeting['region_id']][$key]['footnotes']);
				$time = $symbols[$index] . $time;
			} elseif ($count_footnotes < $count_symbols) {
				$rows[$meeting['region_id']][$key]['footnotes'][$footnote] = $symbols[$count_footnotes];
				$time = $symbols[$count_footnotes] . $time;
			}
		}
	
		//add meeting to row->day array
		$rows[$meeting['region_id']][$key]['days'][$meeting['day']][] = $time;
	}
	
	//add children from the database to the main regions array
	$categories = get_categories('taxonomy=tsml_region');
	foreach ($categories as $category) {
		
		$category->name = html_entity_decode($category->name);
		
		if (array_key_exists($category->term_id, $rows)) {
			usort($rows[$category->term_id], function($a, $b) {
				if ($a['group'] == $b['group']) return strcmp($a['location'], $b['location']);
				return strcmp($a['group'], $b['group']);
			});
		}
		
		//check if this is a sub_region
		if (array_key_exists($category->parent, $regions)) {
			
			//this region has a parent, so make sure that parent has an array for sub_regions
			if (!isset($regions[$category->parent]['sub_regions'])) $regions[$category->parent]['sub_regions'] = array();
	
			//skip if there aren't any rows for this sub_region
			if (!array_key_exists($category->term_id, $rows)) continue;

			//attach the sub_region
			$regions[$category->parent]['sub_regions'][$category->name] = $rows[$category->term_id];
					
		} elseif (array_key_exists($category->term_id, $regions)) {
	
			//this is a main region
			$regions[$category->term_id]['name'] = $category->name;
			$regions[$category->term_id]['description'] = $category->description;
			
			if (array_key_exists($category->term_id, $rows)) {
				$regions[$category->term_id]['rows'] = $rows[$category->term_id];
			}
			
		} else {
			
			//this isn't in the array -- no meetings are assigned
			
		}
	}
	
	//dd($regions);
		
	return $regions;
}
