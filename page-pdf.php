<?php

//output PDF of NYC meeting list using TCPDF

//config
$font_region			= array('helvetica', 'b', 20);
$font_sub_region		= array('helvetica', 'b', 11);
$font_table_header		= array('helvetica', 'b', 9);
$font_table_rows		= array('helvetica', 'r', 7);
$starting_page_number	= 8;
$footer_align_even		= 'L';
$footer_align_odd		= 'R';

//dimensions, in inches
$margins = array(
	'left'				=> .5,
	'right'				=> .5,
	'top'				=> .5,
	'bottom'			=> .5,
);
$first_column_width		= 3;
$table_border_width		= .0001;

//convert dimensions to mm
$inch_converter			= 25.4; //25.4mm to an inch
foreach ($margins as $key=>$value) $margins[$key] *= $inch_converter;
$full_page_width		= 8.5 * $inch_converter;
$inner_page_width		= $full_page_width - ($margins['left'] + $margins['right']);
$inner_page_width		-= 5.9; //calculations above appear to be off by this much
$first_column_width		*= $inch_converter;
$day_column_width		= ($inner_page_width - $first_column_width) / 7;

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

//override header and footer
class MyTCPDF extends TCPDF {

    public function Header() {
    }

    public function Footer() {
	    global $footer_align_even, $footer_align_odd;
		$this->SetY(-15);
		$align = ($this->getPage() % 2 == 0) ? $footer_align_even : $footer_align_odd;
		$this->Cell(0, 10, $this->getAliasNumPage(), 0, false, $align, 0, '', 0, false, 'T', 'M');
	}
}

//create new PDF
$pdf = new MyTCPDF('P', 'mm', 'letter', true, 'UTF-8', false);
$pdf->SetAuthor('New York Inter-Group');
$pdf->SetTitle('Meeting List');
$pdf->setStartingPageNumber($starting_page_number);

//margins
$pdf->SetMargins($margins['left'], $margins['top'], $margins['right']);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(true, 25);

//fonts
//$pdf->SetFont($font_family, '', $font_size, '', true);
//$pdf->setHeaderFont(array($font_family, '', $font_size));
//$pdf->setFooterFont(array($font_family, '', $font_size));
$pdf->setFontSubsetting(true);

foreach ($regions as $region) {
	$pdf->AddPage();
	$pdf->setFont($font_region[0], $font_region[1], $font_region[2]);
	$pdf->setCellPaddings(0, 0, 0, 0);
	$pdf->Cell(0, 6, $region['name'], 0, 1, 'L', 0);	
	$pdf->ln(2);
	if ($region['sub_regions']) {
		foreach ($region['sub_regions'] as $sub_region => $rows) {
			
			//draw sub-region header
			$pdf->setCellPaddings(0, 1, 0, 1);
			$pdf->setFont($font_sub_region[0], $font_sub_region[1], $font_sub_region[2]);
			$pdf->Cell(0, 6, strtoupper($sub_region), array('TB'=>array('width' => .5)), 1, 'L', 0);	
			$pdf->ln(4);
			
			//draw table header
			$pdf->setCellPaddings(1, 1, 1, 1);
			$pdf->setFont($font_table_header[0], $font_table_header[1], $font_table_header[2]);
			$pdf->setTextColor(255);
			$pdf->Cell($first_column_width, 6, strtoupper($sub_region), array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
			$pdf->Cell($day_column_width, 6, 'SUN', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
			$pdf->Cell($day_column_width, 6, 'MON', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
			$pdf->Cell($day_column_width, 6, 'TUE', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
			$pdf->Cell($day_column_width, 6, 'WED', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
			$pdf->Cell($day_column_width, 6, 'THU', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
			$pdf->Cell($day_column_width, 6, 'FRI', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
			$pdf->Cell($day_column_width, 6, 'SAT', array('LTRB'=>array('width' => $table_border_width)), 1, 'C', true);

			//draw table rows
			$pdf->setFont($font_table_rows[0], $font_table_rows[1], $font_table_rows[2]);
			$pdf->setTextColor(0);
			foreach ($rows as $row) {
				//public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false) {
				$pdf->setCellPaddings(2, 2, 2, 2);
				$html = '<strong>' . $row['group'] . '</strong> hi';
				$pdf->MultiCell($first_column_width, 6, $html, array('LTRB'=>array('width' => $table_border_width)), 'L', false, 0, '', '', true, 0, true);
				$pdf->setCellPaddings(1, 2, 1, 2);
				foreach ($row['days'] as $day) {
					$pdf->MultiCell($day_column_width, 6, implode("\n", $day), array('LTRB'=>array('width' => $table_border_width)), 'C', false, 0);
				}
				$pdf->ln();
			}
			$pdf->AddPage();
			
			/*
			$linecount = max(
				$pdf->getNumLines($row['cell1data'], 80),
				$pdf->getNumLines($row['cell2data'], 80),
				$pdf->getNumLines($row['cell3data'], 80)
			);
			*/


			//break;
		}
		//break;

	}
}

/*index
$this->AddPage();
$this->resetColumns();
$this->ChapterTitle($num, $title);
$this->setEqualColumns(3, 57);
$this->selectColumn();
$this->SetTextColor(50, 50, 50);
$this->Write(0, 'foobar baz', '', 0, 'J', true, 0, false, true, 0);
$this->Ln();
*/


$pdf->Output('meeting-list.pdf', 'I');
