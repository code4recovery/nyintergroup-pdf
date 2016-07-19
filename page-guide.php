<?php
$regions = array(
	'manhattan' => array(
		1 => array(10006, 10007, 10013, 10038),
		2 => array(10002, 10012, 10014),
		3 => array(10003, 10009, 10010, 10011),
		4 => array(10001, 10018, 10019, 10020, 10036),
		5 => array(10016, 10017, 10022),
		6 => array(10023, 10024, 10025),
		7 => array(10021, 10028, 10044, 10075),
		8 => array(10026, 10027, 10030, 10031, 10037, 10039),
		9 => array(10029, 10035),
		10 => array(10032, 10033, 10034, 10040),
	),
	'bronx' => array(
		1 => array(10451, 10454, 10455),
		2 => array(10452, 10456, 10457),
		3 => array(10459),
		4 => array(10472, 10473),
		5 => array(10458, 10467, 10468),
		6 => array(10461, 10462, 10465),
		7 => array(10464, 10475, 10469),
		8 => array(10466, 10470),
		9 => array(10463, 10471),
	),
	'brooklyn' => array(
		1 => array(11205, 11206, 11211, 11222),
		2 => array(11201, 11215, 11217, 11231, 11232, 11245),
		3 => array(11213, 11216, 11221, 11225, 11233, 11237, 11238),
		4 => array(11203, 11207, 11208, 11212),
		5 => array(11204, 11209, 11214, 11219, 11220, 11228),
		6 => array(11210, 11218, 11226, 11230),
		7 => array(11234, 11236),
		8 => array(11223, 11224, 11229, 11235),
	),
	'staten-island' => array(
		1 => array(10301, 10304, 10305, 10310),
		2 => array(10302, 10303, 10314),
		3 => array(10306),
		4 => array(10307, 10308, 10309, 10312),
	),
	'queens' => array(
		1 => array(11101, 11102, 11103, 11105, 11106),
		2 => array(11004, 11005, 11361, 11362, 11364),
		3 => array(11368, 11373, 11378),
		4 => array(11354, 11355, 11356, 11357, 11358),
		5 => array(11374, 11375, 11415),
		6 => array(11412, 11423, 11425, 11432, 11433, 11434, 11435, 11436),
		7 => array(11416, 11418, 11419, 11420),
		8 => array(11411, 11422, 11426, 11427, 11428),
		9 => array(11414, 11691, 11692, 11693, 11694, 11695, 11697),
		10 => array(11379, 11385, 11421),
		11 => array(11104, 11370, 11372, 11377),
	),
	'nassau' => 'nassau-county',
	'suffolk' => 'suffolk-county',
	'westchester' => 'westchester-county',
	'rockland' => 'rockland-county',
	'orange' => 'orange-county',
	'putnam' => 'putnam-and-dutchess-counties',
	'sullivan' => 'sullivan-green-and-ulster-counties',
);

$region = $_GET['r'];
$zone = empty($_GET['z']) ? false : intval($_GET['z']);

if (empty($region) || !array_key_exists($region, $regions)) {
	include('page-guide-index.php');
	exit;
}

//define basic query we're going to use
$location_query = array(
	'post_type' => 'locations',
	'numberposts' => -1,
	'orderby' => 'title',
	'order' => 'ASC',
	'fields' => 'ids',
);

//add parameter to post_query
if (is_array($regions[$region])) {
	//nyc borough with zones, add array of ZIPs
	$location_query['meta_query'] = array(
		array(
			'key' => 'postal_code',
			'compare' => 'IN',
			'value' => $regions[$region][$zone],
		)
	);
} else {
	//outlying county, add region parameter
	$term = get_term_by('slug', $regions[$region], 'region');
	$location_query['meta_query'] = array(
		array(
			'key' => 'region',
			'compare' => '=',
			'value' => $term->term_id,
		)
	);
}

//dd($location_query);

//get an array of the ids of all the locations in this region
$locations = get_posts($location_query);

//dd($locations);

if (empty($locations)) {
	$meetings = array();
} else {
	$meetings = tsml_get_meetings(array('location_id' => $locations));
}

$symbols = array(
	'*',   '^',   '#',   '!',   '+',   '@',   '%', 
	'**',  '^^',  '##',  '!!',  '++',  '@@',  '%%',
	'***', '^^^', '###', '!!!', '+++', '@@@', '%%%',
);

$count_symbols = count($symbols);

$rows = array();

$font = 'font-family:Arial,sans-serif; font-size: 6pt;';

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
			'updated' => 0,
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
	}
	
	//set updated if later
	$meeting['updated'] = strtotime($meeting['updated']);
	if ($rows[$key]['updated'] < $meeting['updated']) $rows[$key]['updated'] = $meeting['updated'];
	
	//at least one meeting tagged wheelchair-accessible
	if (($index = array_search('X', $meeting['types'])) !== false) {
		$rows[$key]['wheelchair'] = true;
		unset($meeting['types'][$index]);
	}
	
	//at least one meeting not tagged spanish means row is not spanish
	if (!in_array('S', $meeting['types'])) $rows[$key]['spanish'] = false;
	
	//insert into day
	$time = ''; 
	if (($index = array_search('O',  $meeting['types'])) !== false) {
		$time .= 'O-';  //open meeting
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('BE', $meeting['types'])) !== false) {
		$time .= 'B-';  //beginners meeting
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('D',  $meeting['types'])) !== false) {
		$time .= 'D-'; //discussion meeting
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
	
	//append footnote to array
	if (!empty($meeting['types']) || !empty($meeting['notes'])) {
		//assemble what the footnote should be
		$footnote = array_map('decode_types', $meeting['types']);
		if (!empty($meeting['notes'])) $footnote[] = $meeting['notes'];
		$footnote = implode(', ', $footnote);
		
		//add footnote if not full
		$count_footnotes = count($rows[$key]['footnotes']);
		if (!array_key_exists($footnote, $rows[$key]['footnotes']) && ($count_footnotes < $count_symbols)) {
			$rows[$key]['footnotes'][$footnote] = $symbols[$count_footnotes];
			
			//prepend symbol to time
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

function decode_types($type) {
	global $tsml_types, $tsml_program;
	if (!array_key_exists($type, $tsml_types[$tsml_program])) return '';
	return $tsml_types[$tsml_program][$type];
}

//dd($rows);
?>
			
<table cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px; border-collapse: collapse; <?php echo $font?>">
	<thead>
		<tr style="background-color: black; color: white;">
			<th style="padding: 3px; text-align: center; width: 44%; border:1px solid black;"><p style="margin:0;">MAP ZONE <?php echo $zone?></p></th>
			<th style="padding: 3px; text-align: center; width: 8%; border:1px solid black;"><p style="margin:0;">SUN</p></th>
			<th style="padding: 3px; text-align: center; width: 8%; border:1px solid black;"><p style="margin:0;">MON</p></th>
			<th style="padding: 3px; text-align: center; width: 8%; border:1px solid black;"><p style="margin:0;">TUE</p></th>
			<th style="padding: 3px; text-align: center; width: 8%; border:1px solid black;"><p style="margin:0;">WED</p></th>
			<th style="padding: 3px; text-align: center; width: 8%; border:1px solid black;"><p style="margin:0;">THU</p></th>
			<th style="padding: 3px; text-align: center; width: 8%; border:1px solid black;"><p style="margin:0;">FRI</p></th>
			<th style="padding: 3px; text-align: center; width: 8%; border:1px solid black;"><p style="margin:0;">SAT</p></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($rows as $row) {
		//dd($row);
		?>
	<tr valign="top">
		<td style="padding: 5px; text-align: center; width: 44%; text-align: left; border:1px solid black;">
			<table width="100%" cellspacing="0" cellpadding="0" border="0" style="<?php echo $font?>">
				<tr>
					<td style="font-weight:bold;"><?php echo strtoupper($row['group'])?></td>
					<td align="right"><?php echo date('n/j/y', $row['updated'])?></td>
				</tr>
			</table>
			<p style="margin:0;"><?php echo $row['location']?></p>
			<p style="margin:0;"><?php echo $row['address'] . ' ' . $row['postal_code'];
				if ($row['spanish']) echo ' <strong>SP</strong>';
				if ($row['wheelchair']) echo ' ♿';
				echo '</p>';
			if (!empty($row['notes'])) echo '<p style="margin:0">' . nl2br($row['notes']) . '</p>';
			if (count($row['footnotes'])) {
				echo '<p style="margin:0">';
				foreach ($row['footnotes'] as $footnote => $symbol) {
					echo $symbol . $footnote . ' '; 
				}
				echo '</p>';
			}
			?></td>
		<?php for ($i = 0; $i <= 6; $i++) {?>
		<td style="padding: 5px; text-align: center; width: 8%; border:1px solid black;"><?php echo implode('<br>', $row['days'][$i])?></td>
		<?php }?>
	</tr>
	<?php }?>
	</tbody>
</table>