<?php

//make the home page the meetings post_type archive
add_action('pre_get_posts', function($wp_query){
	if (is_admin()) return; //don't do this to inside pages
	if ($wp_query->get('page_id') == get_option('page_on_front')) {
		$wp_query->set('post_type', 'tsml_meeting');
		$wp_query->set('page_id', '');
		$wp_query->is_page = 0;
		$wp_query->is_singular = 0;
		$wp_query->is_post_type_archive = 1;
		$wp_query->is_archive = 1;
	}
});

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