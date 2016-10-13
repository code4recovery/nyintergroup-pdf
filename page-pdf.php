<?php

//output PDF of NYC meeting list using the TCPDF library

ini_set('max_execution_time', 60);

//config fonts
$font_region			= array('helvetica', 'b', 18);
$font_sub_region		= array('helvetica', 'b', 11);
$font_table_header		= array('helvetica', 'b', 8);
$font_table_rows		= array('dejavusans', 'r', 6.4);
$font_footer			= array('helvetica', 'r', 10);

//config pages
$starting_page_number	= 8;
$footer_align_even		= 'L';
$footer_align_odd		= 'R';

//config dimensions, in inches
$margins = array(
	'left'				=> .5,
	'right'				=> .5,
	'top'				=> .8, //include header
);
$first_column_width		= 3;
$table_border_width		= .1;
$page_threshold			= 1.5; //number of inches of room required to start a new sub-region
$table_gap				= .25; //margin between tables

//convert dimensions to mm
$inch_converter			= 25.4; //25.4mm to an inch
$page_height			= 11 * $inch_converter;
foreach ($margins as $key=>$value) $margins[$key] *= $inch_converter;
$full_page_width		= 8.5 * $inch_converter;
$inner_page_width		= $full_page_width - ($margins['left'] + $margins['right']);
$inner_page_width		-= 5.9; //calculations above appear to be off by this much
$first_column_width		*= $inch_converter;
$page_threshold			*= $inch_converter;
$table_gap				*= $inch_converter;
$day_column_width		= ($inner_page_width - $first_column_width) / 7;
$bottom_limit			= $page_height - 2;
$index					= array();

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

/*do what we can to keep this thing alive
ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');
*/

//run function to attach meeting data to $regions
$regions = attachPdfMeetingData($regions);

//load libraries
require_once('vendor/autoload.php');

class MyTCPDF extends TCPDF {

	public $header = 'Manhattan';
	public $page_number = 1;
	private $blank; //for guessing cell heights

	function __construct() {
		global $margins, $starting_page_number, $font_table_rows;
		parent::__construct();
		$this->SetAuthor('New York Inter-Group');
		$this->SetTitle('Meeting List');
		$this->SetMargins($margins['left'], $margins['top'], $margins['right']);
		$this->NewPage($starting_page_number);
		
		$this->blank = clone $this;
		$this->blank->SetFont($font_table_rows[0], $font_table_rows[1], $font_table_rows[2]);
		$this->blank->SetCellPaddings(1.8, 1.8, 1.8, 1.8);
	}
	
	public function NewRow($lines, $height) {
		global $bottom_limit;
		if (($this->GetY() + $height) > $bottom_limit) $this->NewPage();
	}
	
	public function NewPage($page_number = null) {
		$this->AddPage();
		if ($page_number) {
			$this->page_number = $page_number;
		} else {
			$this->page_number++;
		}
		$this->count_rows = 0;
		$this->count_lines = 0;
	}

    public function Header() {
	    global $footer_align_even, $footer_align_odd, $font_region;
		$this->SetY(9);
		$this->SetFont($font_region[0], $font_region[1], $font_region[2]);
		$this->SetCellPaddings(0, 0, 0, 0);
		$align = ($this->GetPage() % 2 == 0) ? $footer_align_even : $footer_align_odd;
		$this->Cell(0, 6, $this->header, 0, 1, $align, 0);	
    }

    public function Footer() {
	    global $footer_align_even, $footer_align_odd, $font_footer;
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

	public function drawTable($title, $rows) {
		global $font_table_header, $first_column_width, $day_column_width, $table_border_width,
			$font_table_rows, $index;
		
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
			$this->SetCellPaddings(1.8, 1.8, 1.8, 1.8);
			
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
					<td width="80%">' . implode('<br>', $left_column) . '</td>
					<td width="20%" align="right">' . $row['last_contact'] . '</td>
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
			
			$row_height = ($line_count * 2.85) + 3.6;
			
			$row_height = max(
				$row_height,
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
			if ($row['spanish']) $index['Spanish Speaking'][$row['group']] = $this->page_number;
			if ($row['wheelchair']) $index['Wheelchair Accessible'][$row['group']] = $this->page_number;
		}
	}
}

//create new PDF
$pdf = new MyTCPDF('P', 'mm', 'letter', true, 'UTF-8', false);

foreach ($regions as $region) {
	$pdf->header = $region['name'];
	if ($region['sub_regions']) {
		//array_shift($region['sub_regions']);
		foreach ($region['sub_regions'] as $sub_region => $rows) {
			
			//create a new page if there's not enough space
			if (($bottom_limit - $pdf->GetY()) < $page_threshold) {
				$pdf->NewPage();
			}
			
			//draw rows
			$pdf->drawTable($sub_region, $rows);
			
			//draw a gap between tables if there's space
			if (($bottom_limit - $pdf->GetY()) > $table_gap) {
				$pdf->ln($table_gap);
			}
			
		}
	} elseif ($region['rows']) {
		$pdf->NewPage();
		$pdf->drawTable($region['name'], $region['rows']);
	}
}

//index
$pdf->header = 'Index';
$pdf->NewPage();
$pdf->SetEqualColumns(3, 57);
$index_output = '';
ksort($index);
foreach ($index as $category => $rows) {
	ksort($rows);
	$index_output .= '<div style="line-height:1;border-bottom:1px solid black;text-transform:uppercase;font-weight:bold;">' . $category . '</div><table style="cellpadding="0" cellspacing="0" border="0">';
	foreach ($rows as $group => $page) {
		$index_output .= '<tr>
			<td align="left">' . $group . '</td>
			<td align="right">' . $page . '</td>
		</tr>';
	}
	$index_output .= '</table>';
}
$pdf->WriteHTML($index_output, true, false, true, false, '');
$pdf->Ln();

$pdf->Output('meeting-list.pdf', 'I');
