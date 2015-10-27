<?php

add_action('pre_get_posts', function($wp_query){

	//ensure this filter isn't applied to the admin area
	if (is_admin()) return;

	if ($wp_query->get('page_id') == get_option('page_on_front')) {

		$wp_query->set('post_type', 'meetings');
		$wp_query->set('page_id', ''); //Empty

		//set properties that describe the page to reflect that we aren't really displaying a static page
		$wp_query->is_page = 0;
		$wp_query->is_singular = 0;
		$wp_query->is_post_type_archive = 1;
		$wp_query->is_archive = 1;

	}

});