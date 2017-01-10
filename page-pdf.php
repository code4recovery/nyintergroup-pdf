<?php

//show form
if (!isset($_GET['start']) || !isset($_GET['index'])) {
	wp_redirect('/data');
	exit;
}

//output PDF of NYC meeting list using the TCPDF library

ini_set('max_execution_time', 60);

//don't show these in indexes
$exclude_from_indexes	= array('Beginner', 'Candlelight', 'Closed', 'Grapevine', 'Literature', 'Open', 'Topic Discussion');

//config fonts
$font_header			= array('helvetica', 'b', 18);
$font_footer			= array('helvetica', 'r', 10);
$font_table_header	= array('helvetica', 'b', 8);
$font_table_rows		= array('dejavusans', 'r', 6.4); //for the unicode character
$font_index_header	= array('helvetica', 'b', 9);
$font_index_rows		= array('helvetica', 'r', 6);

//config dimensions, in inches
$margins = array(
	'left'				=> .5,
	'right'				=> .5,
	'top'				=> .8, //include header
	'bottom'				=> .5, //include header
);
$first_column_width		= 3;
$table_border_width		= .1;
$page_threshold			= 1; //number of inches of room required to start a new sub-region
$table_gap				= .25; //margin between tables

//convert dimensions to mm
$table_padding			= 1.8; //in mm
$inch_converter			= 25.4; //25.4mm to an inch
$page_width				= 8.5 * $inch_converter;
$page_height				= 11 * $inch_converter;
foreach ($margins as $key=>$value) $margins[$key] *= $inch_converter;
$full_page_width			= 8.5 * $inch_converter;
$inner_page_width		= $full_page_width - ($margins['left'] + $margins['right']);
$first_column_width		*= $inch_converter;
$page_threshold			*= $inch_converter;
$table_gap				*= $inch_converter;
$day_column_width		= ($inner_page_width - $first_column_width) / 7;
$bottom_limit			= $page_height - $margins['bottom'] - 10;
$index = $zip_codes		= array();

//main sections are here manually to preserve book order. these must match the term_ids of the regions
$regions = array(
	2299 => array(), //Manhattan
	2300 => array(), //Bronx
	2303 => array(), //Brooklyn
	2302 => array(), //Staten Island
	2301 => array(), //Queens
	2095 => array(), //Nassau County
	2096 => array(), //Suffolk County
	2098 => array(), //Westchester County
	2158 => array(), //Rockland County
	2117 => array(), //Orange County
	2097 => array(), //Putnam and Dutchess Counties
	2154 => array(), //Sullivan, Green, and Ulster Counties
	2218 => array(), //Connecticut
	2161 => array(), //New Jersey
);

//symbols used in the book, in the order in which they're applied
$symbols = array(
	'*',   '^',   '#',   '!',   '+',   '@',   '%', 
	'**',  '^^',  '##',  '!!',  '++',  '@@',  '%%',
	'***', '^^^', '###', '!!!', '+++', '@@@', '%%%',
);

//must be a logged-in user to run this page (otherwise last_contact will be null)
if (!is_user_logged_in()) {
	auth_redirect();
} elseif (!current_user_can('edit_posts')) {
	die('you do not have access to view this page');
}

//load libraries
require_once('vendor/autoload.php');
require_once('page-pdf-functions.php');
require_once('page-pdf-mytcpdf.php');

//run function to attach meeting data to $regions
$regions = attachPdfMeetingData($regions);

//create new PDF
$pdf = new MyTCPDF();

foreach ($regions as $region) {
	$pdf->header = $region['name'];
	$pdf->NewPage();
	
	if ($region['sub_regions']) {

		//make page jump for city borough zone maps
		if (!in_array($region['name'], array('Manhattan', 'Westchester County'))) $pdf->addPage();
		
		//array_shift($region['sub_regions']);
		foreach ($region['sub_regions'] as $sub_region => $rows) {
			
			//create a new page if there's not enough space
			if (($bottom_limit - $pdf->GetY()) < $page_threshold) {
				$pdf->NewPage();
			}
			
			//draw rows
			$pdf->drawTable($sub_region, $rows, $region['name']);
			
			//draw a gap between tables if there's space
			if (($bottom_limit - $pdf->GetY()) > $table_gap) {
				$pdf->ln($table_gap);
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
$pdf->SetEqualColumns(3, 57);
$pdf->SetCellPaddings(0, 1, 0, 1);
$index_output = '';
foreach ($index as $category => $rows) {
	ksort($rows);
	$pdf->SetFont($font_index_header[0], $font_index_header[1], $font_index_header[2]);
	$pdf->Cell(0, 0, $category, array('B'=>array('width' => .25)), 1);
	$pdf->SetFont($font_index_rows[0], $font_index_rows[1], $font_index_rows[2]);
	foreach ($rows as $group => $page) {
		if (strlen($group) > 33) $group = substr($group, 0, 32) . '…';
		$pdf->Cell(50, 0, $group, array('B'=>array('width' => .1)), 0);
		$pdf->Cell(7, 0, $page, array('B'=>array('width' => .1)), 1, 'R');
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
	$pdf->Cell(20, 0, $zip, array('B'=>array('width' => .1)), 0);
	$pdf->Cell(37, 0, implode(', ', $pages), array('B'=>array('width' => .1)), 1, 'R');
}

$pdf->Output('meeting-list.pdf', 'I');
