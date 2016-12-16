<?php

//must be logged in
if (!is_user_logged_in()) {
	auth_redirect();
} elseif (!current_user_can('edit_posts')) {
	die('you do not have access to view this page');
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
				echo '<h3>' . $region->name . '</h3><ul>';
				foreach ($locations_by_region[$region->term_id] as $location) {
					$meeting = get_posts('post_type=tsml_meeting&fields=ids&post_parent=' . $location['location_id']);
					echo '<li><a href="/wp-admin/post.php?post=' . $meeting[0] . '&action=edit" target="_blank">' . $location['location'] . '</a> ' . $location['formatted_address'] . '</li>';
				}
				echo '</ul>';
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

//output
check_regions(get_regions());