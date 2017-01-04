<?php

//must be logged in
if (!is_user_logged_in()) {
	auth_redirect();
} elseif (!current_user_can('edit_posts')) {
	die('you do not have access to view this page');
}

//special link mode, jump to first meeting 
if (!empty($_GET['location_id'])) {
	$meeting = get_posts('post_type=tsml_meeting&fields=ids&post_status=any&post_parent=' . intval($_GET['location_id']));
	wp_redirect('/wp-admin/post.php?action=edit&post=' . $meeting[0]);
	exit;
}

//clean up
$wpdb->query('DELETE FROM wp_term_relationships WHERE object_id NOT IN (SELECT ID FROM wp_posts);');
$wpdb->query('UPDATE wp_term_taxonomy tt SET count = (SELECT count(p.ID) FROM wp_term_relationships tr LEFT JOIN wp_posts p ON p.ID = tr.object_id WHERE tr.term_taxonomy_id = tt.term_taxonomy_id);');
$wpdb->query('DELETE wt FROM wp_terms a INNER JOIN wp_term_taxonomy b ON a.term_id = b.term_id WHERE b.count = 0;');

//fix groups 
$wpdb->query('UPDATE wp_posts SET post_title = REPLACE(post_title, "(Group ", "") WHERE post_type = "tsml_group"');
$wpdb->query('UPDATE wp_posts SET post_title = REPLACE(post_title, ")", "") WHERE post_type = "tsml_group"');

//get all categories hierarchically
function get_regions($parent=0) {
	$regions = get_terms('taxonomy=tsml_region&parent=' . $parent);
	foreach ($regions as $region) $region->children = get_regions($region->term_id);
	return $regions;
}

//loop through them all and flag 
function check_regions($regions) {
	global $locations_by_region;
	foreach ($regions as $region) {
		if (count($region->children)) {
			if ($region->count) {
				echo '<h4>Region Branch: ' . $region->name . '</h4><ol>';
				foreach ($locations_by_region[$region->term_id] as $location) {
					echo '<li><a href="?location_id=' . $location['location_id'] . '" target="_blank">' . $location['location'] . '</a> ' . $location['formatted_address'] . '</li>';
				}
				echo '</ol>';
			}
			check_regions($region->children);
		}
	}
}

//group all locations by region
$locations_by_region = array();
$locations = tsml_get_locations();
foreach ($locations as $location) {
	if (!array_key_exists($location['region_id'], $locations_by_region)) {
		$locations_by_region[$location['region_id']] = array();
	}
	$locations_by_region[$location['region_id']][] = $location;
}

tsml_assets();

get_header();

echo '<div class="container" id="data">
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h3>Potential Data Issues</h3>
				</div>';

//output
check_regions(get_regions());

//check number of commas in location
$bad_addresses = array(
	'Too Many Commas' => array(),
	'Too Few Commas' => array(),
	'No Street Number' => array(),
);

foreach ($locations as $location) {
	$commas = substr_count($location['formatted_address'], ',');
	if ($commas > 3) {
		$bad_addresses['Too Many Commas'][] = $location;
	} elseif ($commas < 3) {
		$bad_addresses['Too Few Commas'][] = $location;
	} else {
		$words = explode(' ', $location['formatted_address']);
		if (!preg_match("/^[0-9-]+$/", $words[0])) {
			$bad_addresses['No Street Number'][] = $location;
		}
	}
}

foreach ($bad_addresses as $reason => $addresses) {
	if (!count($addresses)) continue;
	//sort($addresses);
	echo '<h4>' . $reason . '</h4><ol>';
	foreach ($addresses as $address) {
		echo '<li><a href="?location_id=' . $address['location_id'] . '" target="_blank">' . $address['location'] . '</a> ' . $address['formatted_address'] . '</li>';
	}
	echo '</ol>';
}

echo '		</div>
		</div>
	</div>';
	
get_footer();