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
));

foreach ($locations as $location) {
	$custom = get_post_meta($location->ID);
}

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
				<?php foreach ($locations as $location) {
					//dd($location);
					$location_meta = get_post_meta($location->ID);
					$updated = strToTime($location->post_modified);
					$meetings = get_posts(array(
						'post_type' => 'meetings',
						'numberposts' => -1,
						'post_parent' => $location->ID,
					));
					$days = array();
					foreach ($meetings as $meeting) {
						$meeting_meta = get_post_meta($meeting->ID);
						$day = $meeting_meta['day'][0];
						$time = $meeting_meta['time'][0];
						if (empty($days[$day])) $days[$day] = array();
						$days[$day][] = $time;
					}
					?>
				<tr>
					<td class="location">
						<div><strong><?php echo strToUpper($location->post_title)?></strong><span class="pull-right"><?php echo date('n/j/y', $updated)?></span></div>
						<div><?php echo $location_meta['address'][0]?> <?php echo $location_meta['postal_code'][0]?></div>
						<div><?php echo $location->post_content?></div>
					</td>
					<?php for ($i = 0; $i < 7; $i++) {?>
					<td>
						<?php
						if (!empty($days[$i])) {
							sort($days[$i]);
							foreach ($days[$i] as $time) {
								if ($time == '12:00') {
									echo '12N';
								} elseif ($time == '23:59') {
									echo '12M';
								} else {
									echo $time . '<br>';									
								}
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