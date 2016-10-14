<?php

//output PDF of NYC meeting list using the TCPDF library

ini_set('max_execution_time', 60);

//don't show these in indexes
$exclude_from_indexes	= array('Beginner', 'Candlelight', 'Closed', 'Grapevine', 'Literature', 'Open', 'Topic Discussion');

//config fonts
$font_header			= array('helvetica', 'b', 18);
$font_footer			= array('helvetica', 'r', 10);
$font_table_header		= array('helvetica', 'b', 8);
$font_table_rows		= array('dejavusans', 'r', 6.4); //for the unicode character
$font_index_header		= array('helvetica', 'b', 9);
$font_index_rows		= array('helvetica', 'r', 6);

//config pages
$starting_page_number	= 8;
$footer_align_even		= 'L';
$footer_align_odd		= 'R';

//config dimensions, in inches
$margins = array(
	'left'				=> .5,
	'right'				=> .5,
	'top'				=> .8, //include header
	'bottom'			=> .5, //include header
);
$first_column_width		= 3;
$table_border_width		= .1;
$page_threshold			= 1; //number of inches of room required to start a new sub-region
$table_gap				= .25; //margin between tables

//convert dimensions to mm
$table_padding			= 1.8; //in mm
$inch_converter			= 25.4; //25.4mm to an inch
$page_width				= 8.5 * $inch_converter;
$page_height			= 11 * $inch_converter;
foreach ($margins as $key=>$value) $margins[$key] *= $inch_converter;
$full_page_width		= 8.5 * $inch_converter;
$inner_page_width		= $full_page_width - ($margins['left'] + $margins['right']);
$first_column_width		*= $inch_converter;
$page_threshold			*= $inch_converter;
$table_gap				*= $inch_converter;
$day_column_width		= ($inner_page_width - $first_column_width) / 7;
$bottom_limit			= $page_height - $margins['bottom'] - 10;
$index = $zip_codes		= array();

//main sections are here manually to preserve book order. these must match the term_ids of the regions
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

//run function to attach meeting data to $regions
$regions = attachPdfMeetingData($regions);

//load libraries
require_once('vendor/autoload.php');

class MyTCPDF extends TCPDF {

	public $header = 'Manhattan';
	public $page_number = 1;
	private $blank; //for guessing cell heights

	function __construct() {
		global $margins, $starting_page_number, $font_table_rows, $page_width, $page_height, $table_padding;
		parent::__construct('P', 'mm', array($page_width, $page_height));
		$this->SetAuthor('New York Inter-Group');
		$this->SetTitle('Meeting List');
		$this->SetMargins($margins['left'], $margins['top'], $margins['right']);
		
		$this->blank = clone $this;
		$this->blank->SetFont($font_table_rows[0], $font_table_rows[1], $font_table_rows[2]);
		$this->blank->SetCellPaddings($table_padding, $table_padding, $table_padding, $table_padding);

		$this->page_number = $starting_page_number - 1;
	}
	
	public function NewRow($lines, $height) {
		global $bottom_limit;
		if (($this->GetY() + $height) > $bottom_limit) $this->NewPage();
	}
	
	public function NewPage() {
		$this->AddPage();
		$this->page_number++;
		$this->count_rows = 0;
		$this->count_lines = 0;
	}

    public function Header() {
	    global $footer_align_even, $footer_align_odd, $font_header;
		$this->SetY(9);
		$this->SetFont($font_header[0], $font_header[1], $font_header[2]);
		$this->SetCellPaddings(0, 0, 0, 0);
		$align = ($this->GetPage() % 2 == 0) ? $footer_align_even : $footer_align_odd;
		$this->Cell(0, 6, $this->header, 0, 1, $align, 0);	
    }

    public function Footer() {
	    global $footer_align_even, $footer_align_odd, $font_footer;
	    if ($this->header == 'Index') return;
		$this->SetY(-15);
		$this->SetFont($font_footer[0], $font_footer[1], $font_footer[2]);
		$this->SetCellPaddings(0, 0, 0, 0);
		$align = ($this->GetPage() % 2 == 0) ? $footer_align_even : $footer_align_odd;
		$this->Cell(0, 10, $this->page_number, 0, false, $align, 0, '', 0, false, 'T', 'M');
	}
	
	private function guessFirstCellHeight($html) {
		global $first_column_width, $row_height, $table_border_width;
		$this->blank->AddPage();
		$start = $this->blank->GetY();
		$this->blank->MultiCell($first_column_width, $row_height, $html, array('LTRB'=>array('width' => $table_border_width)), 'L', false, 1, '', '', true, 0, true);
		$end = $this->blank->GetY();
		$this->blank->DeletePage(1);
		return $end - $start;
	}

	public function drawTable($title, $rows, $region) {
		global $font_table_header, $first_column_width, $day_column_width, $table_border_width,
			$font_table_rows, $index, $exclude_from_indexes, $zip_codes, $table_padding;
		
		//draw table header
		$this->SetCellPaddings(1, 1, 1, 1);
		$this->SetFont($font_table_header[0], $font_table_header[1], $font_table_header[2]);
		$this->setTextColor(255);
		$this->Cell($first_column_width, 6, strtoupper($title), array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, 6, 'SUN', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, 6, 'MON', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, 6, 'TUE', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, 6, 'WED', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, 6, 'THU', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, 6, 'FRI', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, 6, 'SAT', array('LTRB'=>array('width' => $table_border_width)), 1, 'C', true);

		//draw table rows
		$this->SetFont($font_table_rows[0], $font_table_rows[1], $font_table_rows[2]);
		$this->setTextColor(0);
		foreach ($rows as $row) {
			
			//public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false) {
			$this->SetCellPaddings($table_padding, $table_padding, $table_padding, $table_padding);
			
			//build first column
			$left_column = array();
			$left_column[] = '<strong>' . strtoupper($row['group']) . '</strong>';
			if ($row['spanish']) $left_column[0] .= ' <strong>SP</strong>';
			if ($row['wheelchair']) $left_column[0] .= ' ♿';
			$left_column[] = $row['address'] . ' ' . $row['postal_code'];
			if (!empty($row['notes'])) $left_column[] = $row['notes'];
			if (count($row['footnotes'])) {
				$footnotes = '';
				foreach ($row['footnotes'] as $footnote => $symbol) {
					$footnotes .= $symbol . $footnote . ' '; 
				}
				$left_column[] = trim($footnotes);
			}

			$html = '<table width="100%" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td width="85%">' . implode('<br>', $left_column) . '</td>
					<td width="15%" align="right">' . $row['last_contact'] . '</td>
				</tr>
			</table>';
			
			$line_count = max(
				$this->getNumLines(implode("\n", $row['days'][0]), $day_column_width),
				$this->getNumLines(implode("\n", $row['days'][1]), $day_column_width),
				$this->getNumLines(implode("\n", $row['days'][2]), $day_column_width),
				$this->getNumLines(implode("\n", $row['days'][3]), $day_column_width),
				$this->getNumLines(implode("\n", $row['days'][4]), $day_column_width),
				$this->getNumLines(implode("\n", $row['days'][5]), $day_column_width),
				$this->getNumLines(implode("\n", $row['days'][6]), $day_column_width)
			);
			
			$row_height = max(
				($line_count * 2.87) + ($table_padding * 2),
				$this->guessFirstCellHeight($html)
			);

			$this->NewRow($line_count, $row_height);
							
			$this->MultiCell($first_column_width, $row_height, $html, array('LTRB'=>array('width' => $table_border_width)), 'L', false, 0, '', '', true, 0, true);
			$this->SetCellPaddings(1, 2, 1, 2);
			foreach ($row['days'] as $day) {
				$this->MultiCell($day_column_width, $row_height, implode("\n", $day), array('LTRB'=>array('width' => $table_border_width)), 'C', false, 0);
			}
			$this->ln();
			
			//update index
			$row['types'] = array_unique($row['types']);
			$row['types'] = array_map('decode_types', $row['types']);
			$row['types'] = array_diff($row['types'], $exclude_from_indexes);
			foreach ($row['types'] as $type) {
				$index[$type][$row['group']] = $this->page_number;
			}
			$index[$region][$row['group']] = $this->page_number;
			
			if (!empty($row['postal_code'])) {
				if (!array_key_exists($row['postal_code'], $zip_codes)) {
					$zip_codes[$row['postal_code']] = array();
				}
				$zip_codes[$row['postal_code']][] = $this->page_number;
			}
		}
	}
}

//create new PDF
$pdf = new MyTCPDF();

foreach ($regions as $region) {
	$pdf->header = $region['name'];
	$pdf->NewPage();
	
	if ($region['sub_regions']) {
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
