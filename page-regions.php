<?php

//get all categories
function get_regions($parent=0) {
	$regions = get_terms(array(
		'taxonomy' => 'tsml_region',
		'parent' => $parent,
	));
	echo '<ul>';
	foreach ($regions as $region) {
		echo '<li>' . $region->name . ' (' . $region->count . ')';
		get_regions($region->term_id);
		echo '</li>';
	}
	echo '</ul>';
}

dd(get_regions());
