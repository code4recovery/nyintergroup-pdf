<?php


$file = file_get_contents(__DIR__ . '/import.txt');

$rows = explode("\r", $file);

$columns = array_map('sanitize_title', explode("\t", array_shift($rows)));

$meetings = array();

foreach ($rows as $row) {
	$row = array_combine($columns, array_map('format_cell', explode("\t", $row)));
	if (empty($row['day'])) continue;
	$row['day']		 = format_day($row['day']);
	$row['name']	 = format_name($row['name']);
	$row['location'] = format_location($row['location']);
	$row['region']	 = format_region($row['region']);
	$row['state']	 = format_state($row['state'], $row['region']);
	$row['country']	 = 'US';
	$row['notes']	 = format_notes($row['notes']);
	$meetings[]		 = $row;
}

?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">

<table class="table table-striped" style="font-size: 12px;">
	<thead>
		<tr>
			<?php foreach ($columns as $column) {?>
			<th><?php echo $column?></th>
			<?php }?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($meetings as $meeting) {?>
			<tr>
				<?php foreach ($columns as $column) {?>
				<td><?php echo $meeting[$column]?></td>
				<?php }?>
			</tr>
		<?php }?>
	</tbody>
</table>