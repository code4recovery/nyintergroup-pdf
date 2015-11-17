<?php
//this page prepares nyc data from imported tables and passes to importer

//declare vars
$time_start = microtime(true);
$tab = "\t";
$meetings = $subregions = array();
$columns = array('time', 'day', 'name', 'location', 'address', 'city', 'state', 'postal_code', 'notes', 'region', 'updated', 'types', 'subregion', 'country');

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
//SELECT * FROM MeetingDates d LEFT JOIN Meetings m ON m.MeetingID = d.MeetingID WHERE m.street = '' OR d.day = '';
$rows = $wpdb->get_results(file_get_contents(dirname(__FILE__) . '/import.sql'));

foreach ($rows as $row) {
	$row = array_map('format_cell', (array) $row);

	//couldn't be geocoded, but probably defunct: http://www.27east.com/news/article.cfm/Quogue/126460/Demolition-Crews-Knock-Down-Old-VFW-Post-In-Quogue
	if ($row['name'] == 'QOUGUE BELOW THE BAR') continue;
	
	//manual replacements
	if ($row['name'] == 'THANKS GIVING GROUP') {
		$row['city'] = 'Bronxville';
	} elseif ($row['location'] == 'Brick Church Parish Hall') {
		$row['city'] = 'Spring Valley';
	} elseif ($row['address'] == '2021 Albany Post Road') {
		$row['city'] = 'Croton-On-Hudson';
	} elseif ($row['name'] == 'CRESTWOOD GARDENS') {
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
	} elseif ($row['name'] == 'EXCHANGE VIEWS @ ST MARGARET\'S HOUSE') {
		$row['name'] = 'Exchange Views';
		$row['location'] = 'St. Margaret\'s House';		
	} elseif ($row['address'] == '1170 McLeister Street') {
		$row['address'] = '1170 McLester St';
		$row['city'] = 'Elizabeth';
	} elseif (strstr($row['address'], '74 East 17th Street')) {
		$row['location'] = 'Old Park Slope Caton';
	} elseif ($row['address'] == '220 West Houston Street, 2nd Floor') {
		$row['location'] = 'Midnite';
	} elseif ($row['address'] == '411 East 12th Street') {
		$row['location'] = 'The 12th Street Workshop';
	} elseif ($row['address'] == '50 Perry Street, Ground Floor') {
		$row['location'] = 'Perry Street Workshop';
	} elseif ($row['address'] == '1285 Fulton Avenue') {
		$row['location'] = 'Life Recovery Center';
		$row['postal_code'] = '10456';
	} elseif ($row['address'] == '255 Avenue W') {
		$row['location'] = 'Safe Haven';
	} elseif (strstr($row['address'], '122 East 37th Street')) {
		$row['location'] = 'Mustard Seed';
	} elseif (strstr($row['address'], '303 West 42nd Street')) {
		$row['location'] = 'Alanon House';
	} elseif ($row['address'] == '38-21 99th Street') {
		$row['location'] = 'Grupo Honestidad';
	} elseif ($row['address'] == '411 East 12th Street, Basement') {
		$row['location'] = 'The 12th Street Workshop';
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
		$row['notes'] = '2nd Floor Studio 203<br>' . $row['notes'];
	} elseif ($row['city'] == 'Breezy Pt') {
		$row['city'] = 'Queens';
	} elseif ($row['address'] == 'Granite Springs Road') {
		$row['address'] = '39 Granite Springs Rd';
	} elseif ($row['location'] == 'North Woodmere Park Administration Building') {
		$row['address'] = '750 Hungry Harbor Rd';
	} elseif (strstr($row['address'], '65 East 89th Street')) {
		$row['address'] = '65 East 89th Street';
		$row['location'] = 'Church of Saint Thomas More';
		$row['notes'] = 'Rectory Basement<br>' . $row['notes'];
	} elseif ($row['address'] == '135 Foster Avenue') {
		$row['location'] = 'Warwick United Methodist Church';
		$row['address'] = '135 Forester Ave';
	} elseif (strstr($row['address'], '22 Barclay Street')) {
		$row['location'] = 'St. Peter\'s Roman Catholic Church';
		$row['address'] = '22 Barclay Street';
		$row['postal_code'] = '10007';
		$row['notes'] = 'Basement Chapel<br>' . $row['notes'];
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
		$row['notes'] = 'Enter Betw 3rd & 4th Avenues, Basement<br>' . $row['notes'];
	} elseif ($row['address'] == '25 East 15th- Conference Room H') {
		$row['address'] = '25 East 15th Street';
		$row['notes'] = 'Conference Room H<br>' . $row['notes'];
	} elseif ($row['location'] == 'US Merchant Marine Academy') {
		$row['address'] = '25300 Steamboat Road';
		$row['city'] = 'Great Neck';
		$row['notes'] = 'Mariners Chapel<br>Basement Lounge<br>' . $row['notes'];
	} elseif ($row['location'] == 'On the beach at Broadway (In Fair Harbor)') {
		$row['address'] = '315 Broadway';
		$row['city'] = 'Saltaire';
		$row['notes'] = 'On the beach at Broadway (In Fair Harbor)<br>' . $row['notes'];
	} elseif ($row['address'] == '4 West 76th Street Meeting in the gym') {
		$row['address'] = '4 West 76th Street';
		$row['notes'] = 'Meeting in the gym<br>' . $row['notes'];
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
		$row['location'] = 'Living Hope Fellowship';
		$row['address'] = '326 Liberty Drive North';
		$row['city'] = 'Tomkins Cove';
		$row['notes'] = 'Across from Free Hill Road<br>' . $row['notes'];
	}
	
	$row['day']		 	= format_day($row['day']);
	$row['name']	 	= format_name($row['name']);
	$row['location']	= format_location($row['location']);
	$row['postal_code'] = format_postal_code($row);
	$row['subregion']	= format_subregion($row, $subregions);
	$row['region']		= format_region($row['region']);
	$row['city']		= format_city($row);
	$row['state']		= format_state($row['state'], $row['region']);
	$row['country']		= 'US';
	$row['notes']		= format_notes($row['notes']);
	$row['types']		= format_types($row['types']);
	format_address($row);
	if (empty($row['location'])) $row['location'] = $row['name'];
	
	$meetings[]		 = $row;
}

//delete all data and run import
if (true) {
	foreach ($meetings as &$meeting) $meeting = implode($tab, $meeting);		
	array_unshift($meetings, implode($tab, $columns));
	echo tsml_import(implode(PHP_EOL, $meetings), true);
	do_action('admin_notices');
	die('total time ' . (microtime(true) - $time_start) / 60); 
}

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
				<?php foreach ($columns as $column) {?>
				<td><?php echo $meeting[$column]?></td>
				<?php }?>
			</tr>
		<?php }?>
	</tbody>
</table>