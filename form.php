<?php

add_shortcode('pdf-form', function(){

	//security
	if (!is_user_logged_in()) {
		return 'please log in';
	} elseif (!current_user_can('edit_posts')) {
		return 'you do not have access to view this page';
	}

	return '
	<h3>Generate PDF</h3>
	<form method="get" class="form-horizontal" action="' . admin_url('admin-ajax.php') . '">
		<input type="hidden" name="action" value="pdf">
		<div class="form-group">
			<label for="start" class="col-sm-3 control-label">Starting Page Number</label>
			<div class="col-sm-9">
				<select class="form-control" name="start" id="start">' .
					implode(array_map(function($i){
						return '<option value="' . $i . '">' . $i . '</option>';
					}, range(1, 26))) .
				'</select>
			</div>
		</div>
		<div class="form-group">
			<label for="index" class="col-sm-3 control-label">Show Index for Types</label>
			<div class="col-sm-9">
				<select class="form-control" name="index" id="index">
					<option value="yes" selected>Yes</option>
					<option value="no">No</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="index" class="col-sm-3 control-label">Paper Size</label>
			<div class="col-sm-9">
				<div class="radio">
					<label><input type="radio" name="size" value="letter" checked>US Letter (8.5&times;11")</label>
				</div>
				<div class="radio">
					<label><input type="radio" name="size" value="book">Meeting Book (6.5&times;9.5")</label>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-9 col-sm-offset-3">
				<button class="btn btn-primary" type="submit">Generate PDF</button>
			</div>
		</div>
	</form>';
});