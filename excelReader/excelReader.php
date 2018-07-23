<?php 
	require '../vendor/autoload.php';
	// Класс, непосредственно читающий файл
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
	
	// Создаем объект чтения таблицы.
	$reader = new Xlsx();
	// Требуется только чтение. Форматирование и остальные нюансы не нужны.
	$reader->setReadDataOnly(true);
	// Настраиваем фильтр для чтения части файла.
	$filter = new PartReadFilter(1, 1, range('A', 'E'));
	$reader->setReadFilter($filter);
	// Загружаем файл.
	$spreadsheet = $reader->load("cities/DELS_Санкт-Петербург.xlsx");
	// Достаем объект Cells, имеющий доступ к содержимому ячеек
	$cells = $spreadsheet->getActiveSheet()->getCellCollection();
	
	for ($col = 'A', $row = 1; $col <= 'E'; ++$col) {
		echo $cells->get($col.$row)->getValue();
	}
?>
