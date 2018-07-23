<?php 
	require '../vendor/autoload.php';
	// �����, ��������������� �������� ����
	use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
	use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
	use PhpOffice\PhpSpreadsheet\IOFactory;
	
	class PartReadFilter implements IReadFilter	{
		private $_startRow = 0;
		private $_endRow = 0;
		private $_columns = array();
		
		public function PartReadFilter($startRow, $numRows, $columns) {
			$this->_startRow = $startRow;
			$this->_endRow = $startRow + $numRows;
			$this->_columns = $columns;
		}
		
		public function readCell($column, $row, $worksheetName = "") {
			if ((1 == $row) || 
				(($row >= $this->_startRow) && ($row < $this->_endRow))) {
				if (in_array($column, $this->_columns)) {
					return true;
				}
			}
			return false;
		}
	}
	
	// ������� ������ ������ �������.
	$reader = new Xlsx();
	// ��������� ������ ������. �������������� � ��������� ������ �� �����.
	$reader->setReadDataOnly(true);
	// ����������� ������ ��� ������ ����� �����.
	$filter = new PartReadFilter(1, 1, range('A', 'E'));
	$reader->setReadFilter($filter);
	// ��������� ����.
	$spreadsheet = $reader->load("cities/DELS_�����-���������.xlsx");
	// ������� ������ Cells, ������� ������ � ����������� �����
	$cells = $spreadsheet->getActiveSheet()->getCellCollection();
	
	for ($col = 'A', $row = 1; $col <= 'E'; ++$col) {
		echo $cells->get($col.$row)->getValue();
	}
?>
