<?php
//this page prepares nyc data from imported tables and passes to importer

//declare vars
$debug = true; //debug mode doesn't import, displays results and limits to 100
$time_start = microtime(true);
$tab = "\t";
$meetings = $subregions = array();
$columns = array('time', 'day', 'name', 'location', 'address', 'city', 'state', 'postal_code', 'location notes', 'region', 'updated', 'types', 'group', 'sub region', 'country');
$columns_flipped = array_flip($columns);

//security
if (!current_user_can('edit_posts')) die('no permissions');

//build a lookup array of zipcodes->neighborhoods
$areas = $wpdb->get_results('SELECT areaid, area, zone, neighborhood, zipcodes FROM Area');
foreach ($areas as $area) {
	$zips = explode(',', $area->zipcodes);
	foreach ($zips as $zip) {
		$zip = trim($zip);
		if (empty($zip)) continue;
		if (!array_key_exists($zip, $subregions)) $subregions[$zip] = $area->neighborhood;
	}
}
$subregions['10007'] = 'City Hall';
$subregions['10065'] = 'Upper East Side';
$subregions['11245'] = 'Brooklyn Heights';
$subregions['10013'] = 'Tribeca';
$subregions['11228'] = 'Dyker Heights';
$subregions['11425'] = 'Cambria Height';
$subregions['11692'] = 'Rockaway Park';
$subregions['10075'] = 'Upper East Side';
$subregions['10014'] = 'Greenwich Village';
$subregions['10011'] = 'Greenwich Village';

//get nearly all meeting rows
$query = file_get_contents(dirname(__FILE__) . '/import.sql');
if ($debug) $query .= ' LIMIT 100';
$rows = $wpdb->get_results($query);

foreach ($rows as $row) {
		
	$row = array_map('format_cell', (array) $row);
	
	$row['location notes'] = '';

	//couldn't be geocoded, but probably defunct: http://www.27east.com/news/article.cfm/Quogue/126460/Demolition-Crews-Knock-Down-Old-VFW-Post-In-Quogue
	if ($row['groupname'] == 'QOUGUE BELOW THE BAR') continue;
	
	//manual replacements
	if ($row['groupname'] == 'THANKS GIVING GROUP') {
		$row['city'] = 'Bronxville';
	} elseif ($row['location'] == 'Brick Church Parish Hall') {
		$row['city'] = 'Spring Valley';
	} elseif ($row['address'] == '2021 Albany Post Road') {
		$row['city'] = 'Croton-On-Hudson';
	} elseif ($row['groupname'] == 'CRESTWOOD GARDENS') {
		$row['city'] = 'Yonkers';
	} elseif ($row['location'] == 'Temple Kol-Ami') {
		$row['city'] = 'White Plains';
	} elseif ($row['address'] == '137 North Division Street') {
		$row['city'] = 'Peekskill';
	} elseif ($row['location'] == 'Holy Infant Church') {
		$row['address'] = '450 Racebrook Road';
		$row['city'] = 'Orange';
	} elseif ($row['address'] == '502 West165th Street, Basement') {
		$row['address'] = '502 West 165th Street, Basement';
	} elseif (strstr($row['location'], 'Graymoor')) {
		$row['location'] = 'Graymoor Spiritual Life Center';
		$row['address'] = '1320 Route 9, 4th Floor Conference Room';
	} elseif ($row['location'] == 'Helen Hayes Hospital Annex') {
		$row['address'] = '51-55 Route 9W North West';
		$row['city'] = 'Haverstraw';
	} elseif ($row['address'] == '7 East 10th Strert') {
		$row['address'] = '7 East 10th Street';
	} elseif ($row['address'] == 'Routes 100 & 202, Basement') {
		$row['address'] = '331 NY-100, Basement';
		$row['state'] = 'NY';
	} elseif ($row['location'] == 'St. Albans Veterans Hosp') {
		$row['location'] = 'St. Albans Veterans Hospital';
	} elseif ($row['groupname'] == 'EXCHANGE VIEWS @ ST MARGARET\'S HOUSE') {
		$row['groupname'] = 'Exchange Views';
		$row['location'] = 'St. Margaret\'s House';		
	} elseif ($row['address'] == '1170 McLeister Street') {
		$row['address'] = '1170 McLester St';
		$row['city'] = 'Elizabeth';
	} elseif (strstr($row['address'], '74 East 17th Street')) {
		//$row['location'] = 'Old Park Slope Caton';
	} elseif ($row['address'] == '220 West Houston Street, 2nd Floor') {
		//$row['location'] = 'Midnite';
	} elseif ($row['address'] == '411 East 12th Street') {
		//$row['location'] = 'The 12th Street Workshop';
	} elseif ($row['address'] == '50 Perry Street, Ground Floor') {
		//$row['location'] = 'Perry Street Workshop';
	} elseif ($row['address'] == '1285 Fulton Avenue') {
		$row['location'] = 'Life Recovery Center';
		$row['postal_code'] = '10456';
	} elseif ($row['address'] == '255 Avenue W') {
		$row['location'] = 'Safe Haven';
	} elseif (strstr($row['address'], '122 East 37th Street')) {
		//$row['location'] = 'Mustard Seed';
	} elseif (strstr($row['address'], '303 West 42nd Street')) {
		$row['location'] = 'Alanon House';
	} elseif ($row['address'] == '38-21 99th Street') {
		//$row['location'] = 'Grupo Honestidad';
	} elseif ($row['address'] == '411 East 12th Street, Basement') {
		//$row['location'] = 'The 12th Street Workshop';
	} elseif ($row['location'] == 'St Mary\'s Hospital') {
		//not 100% sure about this
		$row['location'] = 'Hoboken University Medical Center';
		$row['address'] = '308 Willow Ave';
	} elseif ($row['location'] == 'Cherry Grove Fire House') {
		$row['address'] = '181 Bayview Walk';
	} elseif ($row['location'] == 'Cornwall Hospital') {
		//not 100% sure about this
		$row['location'] = 'St Luke\'s Cornwall Hospital';
		$row['address'] = '70 Dubois St';
		$row['city'] = 'Newburgh';
	} elseif ($row['address'] == '134 West 29th Street - 2nd floor Studio 203') {
		$row['address'] = '134 West 29th Street';
		$row['location notes'] = '2nd Floor Studio 203<br>' . $row['location notes'];
	} elseif ($row['city'] == 'Breezy Pt') {
		$row['city'] = 'Queens';
	} elseif ($row['address'] == 'Granite Springs Road') {
		$row['address'] = '39 Granite Springs Rd';
	} elseif ($row['location'] == 'North Woodmere Park Administration Building') {
		$row['address'] = '750 Hungry Harbor Rd';
	} elseif (strstr($row['address'], '65 East 89th Street')) {
		$row['address'] = '65 East 89th Street';
		$row['location'] = 'Church of Saint Thomas More';
		$row['location notes'] = 'Rectory Basement<br>' . $row['location notes'];
	} elseif ($row['address'] == '135 Foster Avenue') {
		$row['location'] = 'Warwick United Methodist Church';
		$row['address'] = '135 Forester Ave';
	} elseif (strstr($row['address'], '22 Barclay Street')) {
		$row['location'] = 'St. Peter\'s Roman Catholic Church';
		$row['address'] = '22 Barclay Street';
		$row['postal_code'] = '10007';
		$row['location notes'] = 'Basement Chapel<br>' . $row['location notes'];
	} elseif (strstr($row['address'], '232 West 11th')) {
		$row['postal_code'] = '10014';
	} elseif (strstr($row['address'], '152 west 71st')) {
		$row['location'] = 'Church of the Blessed Sacrament';
		$row['postal_code'] = '10023';
	} elseif (strstr($row['address'], '543 Main St')) {
		$row['location'] = 'Chapel of the Good Shepherd';
		$row['address'] = '543 Main St';
		$row['postal_code'] = '10044';
	} elseif (strstr($row['address'], '560 Sterling Pl')) {
		$row['postal_code'] = '11238';
	} elseif (strstr($row['address'], '98 Richards St')) {
		$row['postal_code'] = '11231';
	} elseif ($row['location'] == 'Yorktown Grange Fair Building') {
		$row['address'] = '99 Moseman Ave';
		$row['city'] = 'Yorktown Heights';
	} elseif ($row['address'] == 'Route 25A (By the Fish Hatchery)') {
		$row['address'] = '1670 NY-25A (By the Fish Hatchery)';
		$row['city'] = 'Cold Spring Harbor';
	} elseif ($row['address'] == 'Old Main Street') {
		$row['address'] = '1176 E Main St';
	} elseif ($row['location'] == 'Central Christian Church') {
		$row['address'] = '71 West St';
	} elseif ($row['address'] == '73rd Street Betw 3rd & 4th Avenues, Basement') {
		$row['address'] = '7320 4th Avenue';
		$row['location notes'] = 'Enter Betw 3rd & 4th Avenues, Basement<br>' . $row['location notes'];
	} elseif ($row['address'] == '25 East 15th- Conference Room H') {
		$row['address'] = '25 East 15th Street';
		$row['location notes'] = 'Conference Room H<br>' . $row['location notes'];
	} elseif ($row['location'] == 'US Merchant Marine Academy') {
		$row['address'] = '25300 Steamboat Road';
		$row['city'] = 'Great Neck';
		$row['location notes'] = 'Mariners Chapel<br>Basement Lounge<br>' . $row['location notes'];
	} elseif ($row['location'] == 'On the beach at Broadway (In Fair Harbor)') {
		$row['address'] = '315 Broadway';
		$row['city'] = 'Saltaire';
		$row['location notes'] = 'On the beach at Broadway (In Fair Harbor)<br>' . $row['location notes'];
	} elseif ($row['address'] == '4 West 76th Street Meeting in the gym') {
		$row['address'] = '4 West 76th Street';
		$row['location notes'] = 'Meeting in the gym<br>' . $row['location notes'];
	} elseif ($row['address'] == '546 East Boston Post Road- Mamaroneck') {
		$row['address'] = '546 E Boston Post Rd';
		$row['city'] = 'Mamaroneck';
	} elseif (strstr($row['address'], '348 Beach 94th Street')) {
		$row['location'] = 'First Congregational Church';
		$row['address'] = '320 Beach 94th St';
		$row['city'] = 'Rockaway Beach';
		$row['postal_code'] = '11693';
	} elseif ($row['city'] == 'Hollis') {
		$row['city'] = 'Queens';
	} elseif ($row['location'] == 'Tomkins Memorial Church') {
		//$row['location'] = 'Living Hope Fellowship';
		$row['address'] = '326 Liberty Drive North';
		$row['city'] = 'Tomkins Cove';
		$row['location notes'] = 'Across from Free Hill Road<br>' . $row['location notes'];
	} elseif ($row['address'] == '90-05 Jamaica Avenue') {
		$row['address'] = '90-05 175th Street';
		$row['city'] = 'Queens';
	} elseif ($row['address'] == '14-51 143rd Street') {
		$row['location'] = 'Holy Trinity Roman Catholic Church';
		$row['location notes'] = 'Rectory';
		$row['city'] = 'Queens';
		$row['state'] = 'NY';
		$row['postal_code'] = '11357';
	} elseif ($row['location'] == 'Staten Island Christian Church') {
		$row['address'] = '3980 Victory Blvd';
		$row['location notes'] = 'At Church St';
		$row['city'] = 'Staten Island';
		$row['state'] = 'NY';
		$row['postal_code'] = '10314';
	} elseif ($row['location'] == 'Hazelden Center') {
		$row['address'] = '283 West Broadway';
		$row['location notes'] = '1st Floor in Back';
		$row['city'] = 'New York';
		$row['state'] = 'NY';
		$row['postal_code'] = '10013';
	} elseif (stristr($row['groupname'], 'Mohegan Lake')) {
		$row['city'] = 'Mohegan Lake';
		$row['state'] = 'NY';
	} elseif (stristr($row['address'], '273 Bowery')) {
		$row['location'] = 'University Settlement';
		$row['city'] = 'New York';
		$row['state'] = 'NY';
		$row['postal_code'] = '10002';
	} elseif ($row['address'] == '33-50 82nd Street') {
		$row['location'] = 'Saint Mark\'s Episcopal Church';
		$row['city'] = 'Jackson Heights';
		$row['state'] = 'NY';
		$row['postal_code'] = '11372';
	}
	
	if (empty($row['location notes'])) $row['location notes'] = '';
	
	if (!empty($row['xstreet'])) {
		$row['location notes'] = trim($row['xstreet']) . '<br>' . $row['location notes'];
	}
	
	//split anything after comma in address, check if it's a city or prepend to notes
	$row['address'] = title_case($row['address']);
	if (substr_count($row['address'], ',')) {
		list ($row['address'], $notes) = explode(',', $row['address'], 2);
		$notes = trim(str_replace(', New York', '', $notes));
		if (in_array($notes, $cities)) {
			$row['city'] = $notes;
		} else {
			$row['location notes'] = trim($notes) . '<br>' . $row['location notes'];
		}
	}
	
	$row['day']		 		= format_day($row['day']);
	$row['name']			= title_case($row['groupname1'] ?: $row['groupname']);
	$row['group']			= strtoupper($row['groupname']) . ' (Grp. #' . $row['groupid'] . ')';
	$row['location']		= format_location($row['location']);
	$row['postal_code'] 	= format_postal_code($row);
	$row['sub region']		= format_subregion($row, $subregions);
	$row['region']			= format_region($row['region']);
	$row['city']			= format_city($row);
	$row['state']			= format_state($row['state'], $row['region']);
	$row['country']			= 'US';
	$row['updated']			= $row['updated'];
	
	//types (and notes)
	if ($row['wc'] == 'WC') $row['types'] .= '<br>WC';
	if ($row['SP'] == 'SP') $row['types'] .= '<br>Spanish Speaking';
	format_types($row);

	format_address($row);
	
	$row['location notes'] = str_replace('(', '', $row['location notes']);
	$row['location notes'] = str_replace(')', '', $row['location notes']);
	$row['location notes'] = str_replace('Betw ', 'Between ', $row['location notes']);
	$row['location notes'] = str_replace('@ ', 'At ', $row['location notes']);
	
	//address by default
	if (empty($row['location'])) $row['location'] = $row['address'];
	
	//only add the correct columns to the array
	$meetings[] = array_values(array_intersect_key(array_merge($columns_flipped, $row), $columns_flipped));
}

//dd($meetings);

//delete all data and run import
if (!$debug) {
	array_unshift($meetings, $columns);
	//dd($meetings);
	echo tsml_import($meetings, true);
	do_action('admin_notices');
	die('total time ' . (microtime(true) - $time_start) / 60); 
}

$columns_count = count($columns);
?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">

<table class="table table-striped" style="font-size: 12px;">
	<thead>
		<tr>
			<?php foreach ($columns as $column) {?>
			<th><?php echo $column?></th>
			<?php }?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($meetings as $meeting) {?>
			<tr>
				<?php for ($i = 0; $i < $columns_count; $i++) {?>
				<td><?php echo $meeting[$i]?></td>
				<?php }?>
			</tr>
		<?php }?>
	</tbody>
</table>