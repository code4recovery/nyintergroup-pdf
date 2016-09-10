<?php
//security
if (!is_user_logged_in()) {
	auth_redirect();
} elseif (!current_user_can('edit_posts')) {
	die('you do not have access to view this page');
}
?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

<div class="container">
	<h1>Printed Meeting Book</h1> 
	<div class="row">
		<div class="col-md-4">
			<h4>Manhattan</h4>
			<ul>
				<?php foreach ($regions['manhattan'] as $zone => $postal_codes) {?>
				<li><a href="/guide?r=manhattan&z=<?php echo $zone?>">Zone <?php echo $zone?></a></li>
				<?php }?>
			</ul>
		</div>
		<div class="col-md-4">
			<h4>Bronx</h4>
			<ul>
				<?php foreach ($regions['bronx'] as $zone => $postal_codes) {?>
				<li><a href="/guide?r=bronx&z=<?php echo $zone?>">Zone <?php echo $zone?></a></li>
				<?php }?>
			</ul>
		</div>
		<div class="col-md-4">
			<h4>Brooklyn</h4>
			<ul>
				<?php foreach ($regions['brooklyn'] as $zone => $postal_codes) {?>
				<li><a href="/guide?r=brooklyn&z=<?php echo $zone?>">Zone <?php echo $zone?></a></li>
				<?php }?>
			</ul>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4">
			<h4>Staten Island</h4>
			<ul>
				<?php foreach ($regions['staten-island'] as $zone => $postal_codes) {?>
				<li><a href="/guide?r=staten-island&z=<?php echo $zone?>">Zone <?php echo $zone?></a></li>
				<?php }?>
			</ul>
		</div>
		<div class="col-md-4">
			<h4>Queens</h4>
			<ul>
				<?php foreach ($regions['queens'] as $zone => $postal_codes) {?>
				<li><a href="/guide?r=queens&z=<?php echo $zone?>">Zone <?php echo $zone?></a></li>
				<?php }?>
			</ul>
		</div>
		<div class="col-md-4">
			<h4>Other</h4>
			<ul>
				<li><a href="/guide?r=nassau">Nassau County</a></li>
				<li><a href="/guide?r=suffolk">Suffolk County</a></li>
				<li><a href="/guide?r=westchester">Westchester County</a></li>
				<li><a href="/guide?r=rockland">Rockland County</a></li>
				<li><a href="/guide?r=orange">Orange County</a></li>
				<li><a href="/guide?r=putnam">Putnam/Dutchess County</a></li>
				<li><a href="/guide?r=sullivan">Sullivan/Greene/Ulster Counties</a></li>
			</ul>
		</div>
	</div>
</div>