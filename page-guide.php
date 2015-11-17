<?php
if (!current_user_can('edit_posts')) die('no permissions');

tsml_assets('public');

get_header();

?>

<div class="container">
	<div class="row">
		<div class="col-md-12">
			<h1>Guide Import Page</h1>
			<textarea class="form-control"></textarea>
		</div>
	</div>
</div>

<?php
get_footer();