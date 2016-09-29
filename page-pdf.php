<?php

//main sections are added manually to preserve book order. these must match the term_ids of the regions
$regions = array(
	1864 => array(), //Manhattan
	1906 => array(), //Bronx
	1887 => array(), //Brooklyn
	1884 => array(), //Staten Island
	1882 => array(), //Queens
	1966 => array(), //Nassau County
	1890 => array(), //Suffolk County
	1886 => array(), //Westchester County
	1863 => array(), //Rockland County
	1881 => array(), //Orange County
	1893 => array(), //Putnam and Dutchess Counties
	1880 => array(), //Sullivan, Green, and Ulster Counties
	1999 => array(), //Connecticut
	1896 => array(), //New Jersey
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

//do what we can to keep this thing alive
ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

//going to be checking this over and over
$count_symbols = count($symbols);

//build an array of table rows for each region all in one shot, to preserve memory
$rows = array();
$meetings = tsml_get_meetings();
foreach ($meetings as $meeting) {
	$key = $meeting['group_id'] . '-' . $meeting['location_id'];
	//make sure array key exists
	if (!array_key_exists($meeting['region_id'], $rows)) {
		$rows[$meeting['region_id']] = array();
	}
	if (!array_key_exists($key, $rows[$meeting['region_id']])) {
		$parts = explode(', ', $meeting['formatted_address']);
		$rows[$meeting['region_id']][$key] = array(
			'group' => $meeting['group'],
			'location' => $meeting['location'],
			'address' => $parts[0],
			'postal_code' => substr($parts[2], 3),
			'notes' => $meeting['location_notes'],
			'last_contact' => date('n/j/y', strtotime($meeting['last_contact'])),
			'wheelchair' => false,
			'spanish' => true,
			'days' => array(
				0 => array(),
				1 => array(),
				2 => array(),
				3 => array(),
				4 => array(),
				5 => array(),
				6 => array(),
			),
			'footnotes' => array(),
		);
	}
		
	//at least one meeting tagged wheelchair-accessible
	if (($index = array_search('X', $meeting['types'])) !== false) {
		$rows[$meeting['region_id']][$key]['wheelchair'] = true;
		unset($meeting['types'][$index]);
	}
	
	//at least one meeting not tagged spanish means row is not spanish
	if (!in_array('S', $meeting['types'])) $rows[$meeting['region_id']][$key]['spanish'] = false;
	
	//insert into day
	$time = ''; 
	if (($index = array_search('D',  $meeting['types'])) !== false) {
		$time .= 'OD-'; //open discussion meeting (comes before open because all ODs are open)
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('O',  $meeting['types'])) !== false) {
		$time .= 'O-';  //open meeting
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('BE', $meeting['types'])) !== false) {
		$time .= 'B-';  //beginners meeting
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('B',  $meeting['types'])) !== false) {
		$time .= 'BB-'; //big book meeting
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('ST', $meeting['types'])) !== false) {
		$time .= 'S-';  //step meeting
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('TR', $meeting['types'])) !== false) {
		$time .= 'T-';  //tradition meeting
		unset($meeting['types'][$index]);
	} elseif (($index = array_search('C',  $meeting['types'])) !== false) {
		$time .= 'C-';  //closed meeting
		unset($meeting['types'][$index]);
	}

	$time .= format_time($meeting['time']);

	//per Janet, don't need Closed meeting type now because it's implied
	if (($index = array_search('C', $meeting['types'])) !== false) {
		unset($meeting['types'][$index]);
	}
		
	//append footnote to array
	if (!empty($meeting['types']) || !empty($meeting['notes'])) {
		//decide what this meeting's footnote should be
		$footnote = array_map('decode_types', $meeting['types']);
		if (!empty($meeting['notes'])) $footnote[] = $meeting['notes'];
		$footnote = implode(', ', $footnote);
		
		//add footnote if not full
		$count_footnotes = count($rows[$meeting['region_id']][$key]['footnotes']);
		//if (!is_array($rows[$meeting['region_id']][$key]['footnotes'])) dd($meeting);
		if (array_key_exists($footnote, $rows[$meeting['region_id']][$key]['footnotes'])) {
			$index = array_search($footnote, $rows[$meeting['region_id']][$key]['footnotes']);
			$time = $symbols[$index] . $time;
		} elseif ($count_footnotes < $count_symbols) {
			$rows[$meeting['region_id']][$key]['footnotes'][$footnote] = $symbols[$count_footnotes];
			$time = $symbols[$count_footnotes] . $time;
		}
	}

	//add meeting to row->day array
	$rows[$meeting['region_id']][$key]['days'][$meeting['day']][] = $time;
}

//clear up some memory
unset($meetings);

//add children from the database to the main regions array
$categories = get_categories('taxonomy=tsml_region');
foreach ($categories as $category) {
	
	//check if this is a sub_region
	if (array_key_exists($category->parent, $regions)) {
		
		//this region has a parent, so make sure that parent has an array for sub_regions
		if (!isset($regions[$category->parent]['sub_regions'])) $regions[$category->parent]['sub_regions'] = array();

		//skip if there aren't any rows for this sub_region
		if (!array_key_exists($category->term_id, $rows)) continue;
		
		//attach the sub_region
		$regions[$category->parent]['sub_regions'][$category->name] = $rows[$category->term_id];
				
	} elseif (array_key_exists($category->term_id, $regions)) {

		//this is a main region
		$regions[$category->term_id]['name'] = $category->name;
		$regions[$category->term_id]['description'] = $category->description;
		
		if (array_key_exists($category->term_id, $rows)) {
			$regions[$category->term_id]['rows'] = $rows[$category->term_id];
		}
		
	} else {
		
		//this isn't in the array
		
	}
}

//free up some more memory
unset($rows);
unset($categories);

require_once('vendor/tcpdf/tcpdf.php');

//create new PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'Letter', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('New York Inter-Group');
$pdf->SetTitle('Meeting List');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguagearray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
//$pdf->SetFont('dejavusans', '', 14, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
//$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

// Set some content to print
$html = <<<EOD
<h1>Welcome to <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a>!</h1>
<i>This is the first example of TCPDF library.</i>
<p>This text is printed using the <i>writeHTMLCell()</i> method but you can also use: <i>Multicell(), writeHTML(), Write(), Cell() and Text()</i>.</p>
<p>Please check the source code documentation and other examples for further information.</p>
<p style="color:#CC0000;">TO IMPROVE AND EXPAND TCPDF I NEED YOUR SUPPORT, PLEASE <a href="http://sourceforge.net/donate/index.php?group_id=128076">MAKE A DONATION!</a></p>
EOD;

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('meeting-list.pdf', 'I');
