<?php

add_action('wp_ajax_pdf', function(){
	global $wpdb, $margins, $font_table_rows, $page_width, $page_height, $table_padding, $font_header, $header_top, $font_footer, $footer_bottom, $first_column_width, $table_border_width, $font_table_rows, $table_padding, $font_table_header, $first_column_width, $day_column_width, $table_border_width, $font_table_rows, $table_padding, $first_column_width, $day_column_width, $table_border_width, $inner_page_height, $font_table_rows, $index, $exclude_from_indexes, $zip_codes, $table_padding, $line_height_ratio;

	//must be a logged-in user to run this page (otherwise last_contact will be null)
	if (!is_user_logged_in()) {
		auth_redirect();
	} elseif (!current_user_can('edit_posts')) {
		die('you do not have access to view this page');
	} elseif (!isset($_GET['start']) || !isset($_GET['index']) || !isset($_GET['size'])) {
		die('variables missing');
	}

	ini_set('max_execution_time', 60);

	//output PDF of NYC meeting list using the TCPDF library

	//don't show these in indexes
	$exclude_from_indexes	= array('Beginner', 'Candlelight', 'Closed', 'Grapevine', 'Literature', 'Open', 'Topic Discussion');

	//config dimensions, in inches
	$table_border_width		= .1;

	//convert dimensions to mm
	$inch_converter			= 25.4; //25.4mm to an inch

	if ($_GET['size'] == 'letter') {
		$table_padding		= 1.8; //in mm
		$header_top			= 9;
		$footer_bottom 		= -15;
		$font_header			= array('helvetica', 'b', 18);
		$font_footer			= array('helvetica', 'r', 10);
		$font_table_header	= array('helvetica', 'b', 8);
		$font_table_rows		= array('dejavusans', 'r', 6.4); //for the unicode character
		$font_index_header	= array('helvetica', 'b', 9);
		$font_index_rows		= array('helvetica', 'r', 6);
		$margins = array(
			'left'			=> .5,
			'right'			=> .5,
			'top'			=> .8, //include header
			'bottom'			=> .5, //include footer
		);
		$page_width			= 8.5 * $inch_converter;
		$page_height			= 11 * $inch_converter;
		$line_height_ratio	= 2.87;
		$index_width			= 57; // in mm
		$table_gap			= .25 * $inch_converter; //gap between tables
	} elseif ($_GET['size'] == 'book') {
		$table_padding		= 1.4; //in mm
		$header_top			= 6;
		$footer_bottom 		= -10;
		$font_header			= array('helvetica', 'b', 16);
		$font_footer			= array('helvetica', 'r', 8);
		$font_table_header	= array('helvetica', 'b', 6);
		$font_table_rows		= array('dejavusans', 'r', 5.4); //for the unicode character
		$font_index_header	= array('helvetica', 'b', 7);
		$font_index_rows		= array('helvetica', 'r', 5.4);
		$margins = array(
			'left'				=> .25,
			'right'				=> .25,
			'top'				=> .65, //include header
			'bottom'				=> .5, //include footer
		);
		$page_width			= 6.5 * $inch_converter;
		$page_height			= 9.5 * $inch_converter;
		$line_height_ratio	= 2.4;
		$index_width			= 47; // in mm
		$table_gap			= .2 * $inch_converter; //gap between tables
	}

	foreach ($margins as $key => $value) $margins[$key] *= $inch_converter;
	$inner_page_width		= $page_width - $margins['left'] - $margins['right'];
	$inner_page_height		= $page_height - $margins['top'] - $margins['bottom'];
	$first_column_width		= $inner_page_width * .37;
	$day_column_width		= ($inner_page_width - $first_column_width) / 7;
	$page_threshold			= .5 * $inch_converter; //amount of space to start a new section
	$index = $zip_codes		= array();

	//main sections are here manually to preserve book order
	$regions = array();
	foreach (array(
		'Manhattan', 
		'Bronx', 
		'Brooklyn', 
		'Staten Island', 
		'Queens', 
		'Nassau County', 
		'Suffolk County', 
		'Westchester County', 
		'Rockland County', 
		'Orange County', 
		'Putnam and Dutchess Counties', 
		'Sullivan, Green, and Ulster Counties', 
		'Connecticut', 
		'New Jersey'
	) as $region) {
		$region_id = $wpdb->get_var('SELECT term_id FROM wp_terms where name = "' . $region . '"');
		if (!$region_id) die('could not find region with name ' . $region);
		$regions[$region_id] = array();
	}

	//symbols used in the book, in the order in which they're applied
	$symbols = array(
		'*',   '^',   '#',   '!',   '+',   '@',   '%', 
		'**',  '^^',  '##',  '!!',  '++',  '@@',  '%%',
		'***', '^^^', '###', '!!!', '+++', '@@@', '%%%',
	);

	//load libraries
	require_once('vendor/autoload.php');
	require_once('mytcpdf.php');

	//run function to attach meeting data to $regions
	$regions = attachPdfMeetingData($regions);

	//create new PDF
	$pdf = new MyTCPDF();

	foreach ($regions as $region) {
		$pdf->header = $region['name'];
		$pdf->NewPage();
		
		if (!empty($region['sub_regions'])) {

			//make page jump for city borough zone maps
			if (!in_array($region['name'], array('Manhattan', 'Westchester County'))) $pdf->addPage();
			
			//array_shift($region['sub_regions']);
			foreach ($region['sub_regions'] as $sub_region => $rows) {
				
				//create a new page if there's not enough space
				if (($inner_page_height - $pdf->GetY()) < $page_threshold) {
					$pdf->NewPage();
				}
				
				//draw rows
				$pdf->drawTable($sub_region, $rows, $region['name']);
				
				//draw a gap between tables if there's space
				if (($inner_page_height - $pdf->GetY()) > $table_gap) {
					$pdf->Ln($table_gap);
				}
				
				//break; //for debugging
			}
		} elseif ($region['rows']) {
			$pdf->drawTable($region['name'], $region['rows'], $region['name']);
		}

		//break; //for debugging
	}

	//index
	ksort($index);
	$pdf->header = 'Index';
	$pdf->NewPage();
	$pdf->SetEqualColumns(3, $index_width);
	$pdf->SetCellPaddings(0, 1, 0, 1);
	$index_output = '';
	foreach ($index as $category => $rows) {
		ksort($rows);
		$pdf->SetFont($font_index_header[0], $font_index_header[1], $font_index_header[2]);
		$pdf->Cell(0, 0, $category, array('B'=>array('width' => .25)), 1);
		$pdf->SetFont($font_index_rows[0], $font_index_rows[1], $font_index_rows[2]);
		foreach ($rows as $group => $page) {
			if ($pos = strpos($group, ' #')) $group = substr($group, 0, $pos);
			if (strlen($group) > 33) $group = substr($group, 0, 32) . '…';
			$pdf->Cell($index_width * .88, 0, $group, array('B'=>array('width' => .1)), 0);
			$pdf->Cell($index_width * .12, 0, $page, array('B'=>array('width' => .1)), 1, 'R');
		}
		$pdf->Ln(4);
	}

	//zips are a little different, because page numbers is an array
	$pdf->SetFont($font_index_header[0], $font_index_header[1], $font_index_header[2]);
	$pdf->Cell(0, 0, 'ZIP Codes', array('B'=>array('width' => .25)), 1);
	$pdf->SetFont($font_index_rows[0], $font_index_rows[1], $font_index_rows[2]);
	ksort($zip_codes);
	foreach ($zip_codes as $zip => $pages) {
		$pages = array_unique($pages);
		$pdf->Cell($index_width * .35, 0, $zip, array('B'=>array('width' => .1)), 0);
		$pdf->Cell($index_width * .65, 0, implode(', ', $pages), array('B'=>array('width' => .1)), 1, 'R');
	}

	$pdf->Output($_GET['size'] . '.pdf', 'I');

	exit;
});