<?php
tsml_assets('public');

get_header();

$zone = empty($_GET['zone']) ? 1 : $_GET['zone'];

$zones = array(
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
);

$locations = get_posts(array(
	'post_type' => 'locations',
	'numberposts' => -1,
	'meta_query' => array(
		array(
			'key' => 'postal_code',
			'compare' => 'IN',
			'value' => $zones[$zone],
		)
	),
	'orderby' => 'title',
	'order' => 'ASC',
	'fields' => 'ids',
));

$meetings = tsml_get_meetings(array('location_id'=> $locations));

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
			'updated' => 0,
			'wheelchair' => false,
			'spanish' => true,
			'days' => array(),
		);
	}
	
	//set updated if later
	$meeting['updated'] = strtotime($meeting['updated']);
	if ($rows[$key]['updated'] < $meeting['updated']) $rows[$key]['updated'] = $meeting['updated'];
	
	//insert into day
	if (!isset($rows[$key]['days'][$meeting['day']])) $rows[$key]['days'][$meeting['day']] = array();
	$rows[$key]['days'][$meeting['day']][] = $meeting['time'];
	
	//at least one meeting tagged wheelchair-accessible
	if (in_array('X', $meeting['types'])) $rows[$key]['wheelchair'] = true;
	
	//at least one meeting not tagged spanish means row is not spanish
	if (!in_array('S', $meeting['types'])) $rows[$key]['spanish'] = false;
	
}

usort($rows, function($a, $b) {
	if ($a['group'] == $b['group']) return strcmp($a['location'], $b['location']);
	return strcmp($a['group'], $b['group']);
});

//dd($rows);
?>

<script>
jQuery(function($){
	$('#zone').on('change', function(e) {
		document.location.href = '/guide?zone=' + $(this).val();
	});	
});
</script>

<div class="container">
	<div class="row">
		<div class="col-md-12">
			<select class="form-control" id="zone">
				<?php foreach ($zones as $z => $zips) {?>
				<option value="<?php echo $z?>" <?php selected($zone, $z)?>>Zone <?php echo $z?></option>
				<?php }?>
			</select>
			<table border="1">
				<thead>
					<tr>
						<th class="location">MAP ZONE <?php echo $zone?></th>
						<th>SUN</th>
						<th>MON</th>
						<th>TUE</th>
						<th>WED</th>
						<th>THU</th>
						<th>FRI</th>
						<th>SAT</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($rows as $row) {
					//dd($row);
					?>
				<tr valign="top">
					<td class="location">
						<div><strong><?php echo strToUpper($row['group'])?></strong><span class="pull-right"><?php echo date('n/j/y', $row['updated'])?></span></div>
						<div><?php echo $row['location']?></div>
						<div><?php echo $row['address'] . ' ' . $row['postal_code'];
							if ($row['spanish']) echo ' <strong>SP</strong>';
							if ($row['wheelchair']) echo ' <strong>WC</strong>';
							?>
						</div>
						<div><?php echo $row['notes']?></div>
					</td>
					<?php for ($i = 0; $i <= 6; $i++) {?>
					<td>
						<?php
						if (!empty($row['days'][$i])) {
							foreach ($row['days'][$i] as $time) {
								echo format_time($time) . '<br>';
							}
						}
						?>
					</td>
					<?php }?>
				</tr>
				<?php }?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php
get_footer();