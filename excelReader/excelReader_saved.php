<?php 
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
				if (in_array($column, $this->_columns) {
					return true;
				}
			}
			return false;
		}
	}
	
	class SimpleDirection {
		public $area = "AREA";
		public $destination = "DESTINATION";
		public $date = "N/I";
		public $coeff = 1.0;
		public $zone = 0;
		
		public function SimpleDirection($area, $destination, $date, $coeff, $zone) {
			if (null != $area) $this->area = $area;
			if (null != $destination) $this->destination = $destination;
			if (null != $date) $this->date = $date;
			if (null != $coeff) $this->coeff = $coeff;
			if (null != $zone) $this->zone = $zone;
		}
	}
	
	class BaseCity {
		private $cityName = "City";
		private $directions = array();
		
		public function addDirection($area, $destination, $date, $coeff, $zone) {
			$directions[] = new SimpleDirection($area, $destination, $date, $coeff, $zone);
		}
	}
	
	class ZoneMode {
		public $modeType = "MODE_TYPE";
		public $number = 0;
		public $firstCost = 0;
		public $secondCost = 0;
		public $thirdCost = 0;
		
		public function ZoneMode($modeType, $number, $firstCost, $secondCost, $thirdCost) {
			if (null != $modeType) $this->modeType = $modeType;
			if (null != $number) $this->number = $number;
			if (null != $firstCost) $this->firstCost = $firstCost;
			if (null != $secondCost) $this->secondCost = $secondCost;
			if (null != $thirdCost) $this->thirdCost = $thirdCost;
		}
	}
	
	class Zone {
		private $modes = array();
		
		public function addMode($modeType, $number, $firstCost, $secondCost, $thirdCost) {
			$modes[] = new ZoneMode($modeType, $number, $firstCost, $secondCost, $thirdCost);
		}
	}
	
	class DataBase {
		private static $cityFiles[] = glob("cities/DELS_*.xlsx");
		private $cities = array();
		private static $dataObj = null;
		private $zones = array();
		
		private function DataBase() {
			for ($i = 0; $i < count($cityFiles); ++$i) {
				$cityName = sscanf($cityFiles[$i], "cities/DELS_%s.xlsx");
				// Создаем объект чтения таблицы.
				$reader = new Xlsx();
				// Требуется только чтение. Форматирование и остальные нюансы не нужны.
				$reader->setReadDataOnly(true);
				// Читаем файлы по частям.
				for ($i = 0, $flag = true; (true == $flag); ++$i) {
					$startRow = $i * 1000 + 1;
					// Настраиваем фильтр для чтения части файла.
					$filter = new PartReadFilter($startRow, 1000, range('A', 'E'));
					$reader->setReadFilter($filter);
					// Загружаем файл.
					$spreadsheet = $reader->load($cityFiles[$i]);
					// Достаем объект Cells, имеющий доступ к содержимому ячеек
					$cells = $spreadsheet->getActiveSheet()->getCellCollection();
					
					for ($row = $startRow; $row <= $cells->getHighestRow(); ++$row) {
						
					}
				}
			}
		}
		
		public static function getDataObj() {
			if (null == self::$dataObj) {
				
			}
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
