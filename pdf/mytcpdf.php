<?php

class MyTCPDF extends TCPDF {

	public $header;
	private $blank; //for guessing cell heights

	function __construct() {
		global $margins, $font_table_rows, $page_width, $page_height, $table_padding;
		parent::__construct('P', 'mm', array($page_width, $page_height));
		$this->SetAuthor('New York Inter-Group');
		$this->SetTitle('Meeting List');
		$this->SetMargins($margins['left'], $margins['top'], $margins['right']);
		$this->SetAutoPageBreak(true, $margins['bottom']);
		
		$this->blank = clone $this;
		$this->blank->SetFont($font_table_rows[0], $font_table_rows[1], $font_table_rows[2]);
		$this->blank->SetCellPaddings($table_padding, $table_padding, $table_padding, $table_padding);
	}
	
	public function NewPage() {
		$this->AddPage();
		$this->count_rows = 0;
		$this->count_lines = 0;
	}

    public function Header() {
	    global $font_header, $header_top;
	    $page = $this->getPage() + $_GET['start'] - 1;
		$this->SetY($header_top);
		$this->SetFont($font_header[0], $font_header[1], $font_header[2]);
		$this->SetCellPaddings(0, 0, 0, 0);
		$align = ($page % 2) ? 'L' : 'R';
		$this->Cell(0, 6, $this->header, 0, 1, $align, 0);	
    }

    public function Footer() {
	    global $font_footer, $footer_bottom;
	    $page = $this->getPage() + $_GET['start'] - 1;
		$this->SetY($footer_bottom);
		$this->SetFont($font_footer[0], $font_footer[1], $font_footer[2]);
		$this->SetCellPaddings(0, 0, 0, 0);
		$align = ($page % 2) ? 'R' : 'L';
		//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M') {
		$this->Cell(0, 0, $page, 0, false, $align);
	}
	
	private function guessFirstCellHeight($html) {
		global $first_column_width, $table_border_width, $font_table_rows, $table_padding;
		$this->blank->AddPage();
		$start = $this->blank->GetY();
		$this->blank->MultiCell($first_column_width, 0, $html, array('LTRB'=>array('width' => $table_border_width)), 'L', false, 1, '', '', true, 0, true);
		$end = $this->blank->GetY();
		$this->blank->DeletePage(1);
		return $end - $start;
	}

	public function drawTableHeader($title) {
		global $font_table_header, $first_column_width, $day_column_width, $table_border_width, $font_table_rows, $table_padding;

		$height = 6;

		//draw table header
		$this->SetCellPaddings(1, 1, 1, 1);
		$this->SetFont($font_table_header[0], $font_table_header[1], $font_table_header[2]);
		$this->SetTextColor(255);
		$this->Cell($first_column_width, $height, strtoupper($title), array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, $height, 'SUN', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, $height, 'MON', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, $height, 'TUE', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, $height, 'WED', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, $height, 'THU', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, $height, 'FRI', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Cell($day_column_width, $height, 'SAT', array('LTRB'=>array('width' => $table_border_width)), 0, 'C', true);
		$this->Ln();

		//reset for table
		$this->SetFont($font_table_rows[0], $font_table_rows[1], $font_table_rows[2]);
		$this->SetCellPaddings($table_padding, $table_padding, $table_padding, $table_padding);
		$this->setTextColor(0);
	}

	public function drawTable($title, $rows, $region) {
		global $first_column_width, $day_column_width, $table_border_width, $inner_page_height,
			$font_table_rows, $index, $exclude_from_indexes, $zip_codes, $table_padding, $line_height_ratio;
		
		$this->drawTableHeader($title);

		//draw table rows
		foreach ($rows as $row) {
						
			//build first column
			$group_title = strtoupper($row['group']);
			if ($row['spanish']) $group_title .= ' SP';
			if ($row['wheelchair']) $group_title .= ' ♿';
			$left_column = array();
			if (!empty($row['location']) && ($row['location'] != $row['address'])) $left_column[] = $row['location'];
			$left_column[] = $row['address'] . ' ' . $row['postal_code'];
			if (!empty($row['notes'])) $left_column[] = $row['notes'];
			if (count($row['footnotes'])) {
				$footnotes = '';
				foreach ($row['footnotes'] as $footnote => $symbol) {
					$footnotes .= $symbol . $footnote . ' '; 
				}
				$left_column[] = trim($footnotes);
			}

			//floats and position: absolute don't seem to work, not sure how else to right-align this row
			$html = '<table width="100%" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td width="80%"><strong>' . $group_title . '</strong></td>
					<td width="20%" align="right">' . $row['last_contact'] . '</td>
				</tr>
			</table>' . implode('<br>', $left_column);
			
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
				($line_count * $line_height_ratio) + ($table_padding * 2),
				$this->guessFirstCellHeight($html)
			);
			
			//why on earth is $row_height not necessary here?
			if (($this->GetY() + 10) > $inner_page_height) {
				$this->NewPage();
				$this->drawTableHeader($title);
			}
							
			$this->MultiCell($first_column_width, $row_height, $html, array('LTRB'=>array('width' => $table_border_width)), 'L', false, 0, '', '', true, 0, true);
			foreach ($row['days'] as $day) {
				$this->MultiCell($day_column_width, $row_height, implode("\n", $day), array('LTRB'=>array('width' => $table_border_width)), 'C', false, 0);
			}
			$this->Ln();
			
			$page = $this->getPage() + $_GET['start'] - 1;
			
			//update index
			$row['types'] = array_unique($row['types']);
			$row['types'] = array_map('decode_types', $row['types']);
			$row['types'] = array_diff($row['types'], $exclude_from_indexes);
			if ($_GET['index'] == 'yes') {
				foreach ($row['types'] as $type) {
					$index[$type][$row['group']] = $page;
				}
			}
			
			$index[$region][$row['group']] = $page;
			
			if (!empty($row['postal_code'])) {
				if (!array_key_exists($row['postal_code'], $zip_codes)) {
					$zip_codes[$row['postal_code']] = array();
				}
				$zip_codes[$row['postal_code']][] = $page;
			}
		}
	}
}
