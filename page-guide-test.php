<?php
	
//security	
if (!is_user_logged_in()) {
	auth_redirect();
} elseif (!current_user_can('edit_posts')) {
	die('you do not have access to view this page');
}

//need these later
$symbols = array(
	'*',   '^',   '#',   '!',   '+',   '@',   '%', 
	'**',  '^^',  '##',  '!!',  '++',  '@@',  '%%',
	'***', '^^^', '###', '!!!', '+++', '@@@', '%%%',
);

$count_symbols = count($symbols);

ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

//start html output
$html = '<style type="text/css">
	h1 { text-transform: uppercase; font-size: 16px; }
	h2 { border-top: 2px solid black; border-bottom: 2px solid black; font-size: 12px; padding: 2px 0 3px; text-transform: uppercase; }
	table { font-family: Helvetica; font-size: 9px; border-spacing: 0; border-collapse: collapse; width: 100%; }
	th, td { border: 1px solid black; padding: 3px; vertical-align: top; }
	th { background-color: black; color: white; padding: 3px; align: center; text-transform: uppercase; }
	table table td { border: 0; padding: 0; }
	table.main { page-break-after: always; }
	td.day { text-align: center; width: 7.5%; }
	td.info { position: relative; }
	td.group_name { font-weight: bold; }
	td.last_contact { text-align: right; }
	#footer { position: fixed; left: 0; text-align: right; font-size: 12px; bottom: -25px; right: 0px; height: 24px; }
	#footer:after { content: counter(page); }
	#footer:nth-child(even) { text-align: left; }
	mark { font-family: DejaVu Sans; line-height: .7; margin-left: 3px; }
</style>

<div id="footer">
	Page
</div>
';

//get top level regions
$regions = get_categories(array(
	'taxonomy' => 'region',
	'parent' => 0,
	'include' => 1864,
	//'include' => 1999,
));

//dd($regions);

foreach ($regions as $region) {
	$html .= '<h1>' . $region->name . '</h1>';
	if ($children = get_categories(array(
			'taxonomy' => 'region',
			'parent' => $region->term_id,
			'number' => 1,
		))) {
		foreach ($children as $child) {
			$html .= '<h2>' . $child->name . '</h2>';
			$html .= render_table($child);
		}
	} else {
		$html .= render_table($region);
	}
	$html .= '<script language="text/php">
		foreach ($GLOBALS as $key=>$value) {
			echo $key;
		}
	</script>';
}

//need this for formatting the meeting types
function decode_types($type) {
	global $tsml_types, $tsml_program;
	if (!array_key_exists($type, $tsml_types[$tsml_program])) return '';
	return $tsml_types[$tsml_program][$type];
}

//render the html for a given meta_query
function render_table($region) {
	global $symbols, $count_symbols;
	$meetings = tsml_get_meetings(array('region' => $region->term_id));
	
	$rows = array();
	
	//loop through array of meetings and make one row per group at location
	foreach ($meetings as $meeting) {
		$key = $meeting['group_id'] . '-' . $meeting['location_id'];
		
		//create row
		if (!array_key_exists($key, $rows)) {
			//add new row
			$rows[$key] = array(
				'group' => $meeting['group'],
				'location' => $meeting['location'],
				'address' => $meeting['address'],
				'postal_code' => $meeting['postal_code'],
				'notes' => $meeting['location_notes'],
				'last_contact' => strtotime($meeting['last_contact']),
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
			);
			if (!empty($meeting['group_notes'])) {
				if (!empty($meeting['location_notes'])) $rows[$key]['notes'] .= '<br>';
				$rows[$key]['notes'] .= $meeting['group_notes'];
			}
		}
		
		//at least one meeting tagged wheelchair-accessible
		if (($index = array_search('X', $meeting['types'])) !== false) {
			$rows[$key]['wheelchair'] = true;
			unset($meeting['types'][$index]);
		}
		
		//at least one meeting not tagged spanish means row is not spanish
		if (!in_array('S', $meeting['types'])) $rows[$key]['spanish'] = false;
		
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
			$count_footnotes = count($rows[$key]['footnotes']);
			if (array_key_exists($footnote, $rows[$key]['footnotes'])) {
				$index = array_search($footnote, $rows[$key]['footnotes']);
				$time = $symbols[$index] . $time;
			} elseif ($count_footnotes < $count_symbols) {
				$rows[$key]['footnotes'][$footnote] = $symbols[$count_footnotes];
				$time = $symbols[$count_footnotes] . $time;
			}
		}
		
		//add meeting to row->day array
		$rows[$key]['days'][$meeting['day']][] = $time;
	}
	
	usort($rows, function($a, $b) {
		if ($a['group'] == $b['group']) return strcmp($a['location'], $b['location']);
		return strcmp($a['group'], $b['group']);
	});
	
	$return = '<table class="main">
		<thead>
			<tr>
				<th class="info">' . $region->name . '</th>
				<th class="day">SUN</th>
				<th class="day">MON</th>
				<th class="day">TUE</th>
				<th class="day">WED</th>
				<th class="day">THU</th>
				<th class="day">FRI</th>
				<th class="day">SAT</th>
			</tr>
		</thead>
		<tbody>';
		
	foreach ($rows as $row) {
		
		if ($pos = strpos($row['group'], ' (')) $row['group'] = substr($row['group'], 0, $pos);
		
		$return .= '
		<tr valign="top">
			<td class="info">
				<table>
					<tr>
						<td class="group_name">' . $row['group'] . '</td>
						<td class="last_contact">';
							if ($row['last_contact']) $return .= date('n/j/y', $row['last_contact']);
							$return .= '
						</td>
					</tr>
				</table>';
		if ($row['location'] != $row['address']) $return .= '<div>' . $row['location'] . '</div>';
		$return .= $row['address'] . ' ' . $row['postal_code'];
		if ($row['spanish']) $return .= ' <strong>SP</strong>';
		if ($row['wheelchair']) $return .= '<mark>♿</mark>';
		if (!empty($row['notes'])) $return .= '<p style="margin:0">' . nl2br($row['notes']) . '</p>';
		if (count($row['footnotes'])) {
			$return .= '<p style="margin:0">';
			foreach ($row['footnotes'] as $footnote => $symbol) {
				$return .= $symbol . $footnote . ' '; 
			}
			$return .= '</p>';
		}
		$return .= '<script type="text/php">
			$GLOBALS[\'Manhattan\'][\'' . str_replace("'", "\\'", $row['group']) . '\'] = $pdf->get_page_number();
		</script>';
		$return .= '</td>';
		for ($i = 0; $i <= 6; $i++) {
			$return .= '<td class="day">' . implode('<br>', $row['days'][$i]) . '</td>';
		}
		$return .= '</tr>';
	}
	
	$return .= '</tbody></table>';
	
	return $return;
}

//debug as web page?
//die($html);

//output to PDF
require 'vendor/autoload.php';
$options = new Dompdf\Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isPhpEnabled', true);
$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->render();
dd($GLOBALS);
$dompdf->stream('printed-guide', array('Attachment' => false));