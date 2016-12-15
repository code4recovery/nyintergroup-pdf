<?php

class MyTCPDF extends TCPDF {

	public $header;
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
