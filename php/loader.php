<?php
    require '../vendor/autoload.php';
	// Класс, непосредственно читающий файл
	use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
	use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
	use PhpOffice\PhpSpreadsheet\IOFactory;

    class LoadFilter implements IReadFilter {
        private $_startRow = 0;
		private $_endRow = 0;
		private $_columns = array();
		
		public function __construct($startRow, $numRows, $columns) {
			$this->_startRow = $startRow;
			$this->_endRow = $startRow + $numRows;
			$this->_columns = $columns;
		}
        
        public function setRows($startRow, $numRows) {
            $this->_startRow = $startRow;
			$this->_endRow = $startRow + $numRows;
        }
		
		public function readCell($column, $row, $worksheetName = "") {
			if ((1 === $row) || 
				(($row >= $this->_startRow) && ($row < $this->_endRow))) {
				if (in_array($column, $this->_columns)) {
					return true;
				}
			}
			return false;
		}
    }

    class ExcelFile {

        private $_path = null;
        private $_numEntries = 0;
        private $_baseCity = null;
        private $_list = null;
        private $_startRow = 1;
        private $_stepRows = 1000;
        private $_result = null;
        private $_isEnd = false;
        
        public function __construct($path) {
            $this->_path = $path;
            // Создаем объект чтения таблицы.
            $reader = new Xlsx();
            // Требуется только чтение. Форматирование и остальные нюансы не нужны.
            $reader->setReadDataOnly(true);
            // Настраиваем фильтр для чтения части файла.
            $filter = new LoadFilter($this->_startRow, $this->_stepRows, range('A', 'G'));
            $reader->setReadFilter($filter);
            // Загружаем файл.
            $spreadsheet = $reader->load($this->_path);
            // Достаем объект Cells, имеющий доступ к содержимому ячеек
            $cells = $spreadsheet->getActiveSheet()->getCellCollection();
            if (("Направление" !== $cells->get('A'.'2')->getValue()) || 
                ("Населенный пункт" !== $cells->get('B'.'2')->getValue()) || 
                ("СТАНДАРТ" !== $cells->get('C'.'2')->getValue()) || 
                ("ЭКСПРЕСС" !== $cells->get('D'.'2')->getValue()) || 
                ("СУПЕРЭКСПРЕСС" !== $cells->get('E'.'2')->getValue()) || 
                ("Зона доставки" !== $cells->get('F'.'2')->getValue()) || 
                ("Коэффициент" !== $cells->get('G'.'2')->getValue()))
            {
                $this->_result = false;
            }
            else {
                $this->_list = $this->readCells($cells, 3, $this->_startRow + $this->_stepRows);
                if ((null !== $this->_list) && (0 < count($this->_list))) {
                    $this->_startRow = $this->_startRow + $this->_stepRows;
                    $this->_numEntries += count($this->_list);
                    $this->_result = true;
                } else {
                    $this->_result = false;
                }
            }
        }
        
        public function readNextPart() {
            if ((true === $this->_result) && (false === $this->_isEnd)) {
                // Создаем объект чтения таблицы.
                $reader = new Xlsx();
                // Требуется только чтение. Форматирование и остальные нюансы не нужны.
                $reader->setReadDataOnly(true);
                // Настраиваем фильтр для чтения части файла.
                $filter = new LoadFilter($this->_startRow, $this->_stepRows, range('A', 'G'));
                $reader->setReadFilter($filter);
                // Загружаем файл.
                $spreadsheet = $reader->load($this->_path);
                // Достаем объект Cells, имеющий доступ к содержимому ячеек
                $cells = $spreadsheet->getActiveSheet()->getCellCollection();
                // Читаем таблицу и формируем список:
                $this->_list = $this->readCells($cells, $this->_startRow, $this->_startRow + $this->_stepRows);
                if ((null !== $this->_list) && (0 < count($this->_list))) {
                    $this->_startRow = $this->_startRow + $this->_stepRows;
                    $this->_numEntries += count($this->_list);
                    return true;
                } else {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        
        public function getCurPart() { return $this->_list; }
        
        public function getNumEntries() { return $this->_numEntries; }
        
        public function getBaseCity() { return $this->_baseCity; }
        
        public function getResult() { return $this->_result; }
        
        private function readCells($cells, $startRow, $endRow) {
            $tmpList = null;
            for ($row = $startRow, $index = 0; $row < $endRow; ++$row, ++$index) {
                $entry = null;
                // Читаем край/область и город доставки:
                $area = $this->getArea($cells->get('A'.$row));
                $city = $this->getCity($cells->get('B'.$row));
                if ((null !== $city) && (null !== $area)) {
                    $entry["city"] = "$city ($area)";
                } else {
                    $this->_isEnd = true;
                    break;
                }
                $standart = $this->getLimit($cells->get('C'.$row));
                if (null !== $standart) {
                    $entry["mode"]["standart"] = $standart;
                } else {
                    $this->_isEnd = true;
                    break;
                }
                $express = $this->getLimit($cells->get('D'.$row));
                if (null !== $express) {
                    $entry["mode"]["express"] = $express;
                }
                $spExpress = $this->getLimit($cells->get('E'.$row));
                if (null !== $spExpress) {
                    $entry["mode"]["superexpress"] = $spExpress;
                }
                $zone = $this->getZone($cells->get('F'.$row));
                if (null !== $zone) {
                    $entry["zone"] = $zone;
                } else {
                    $this->_isEnd = true;
                    break;
                }
                $coeff = $this->getCoeff($cells->get('G'.$row));
                if (null !== $coeff) {
                    $entry["coeff"] = $coeff;
                } else {
                    $this->_isEnd = true;
                    break;
                }
                $tmpList[$index] = $entry;
            }
            return $tmpList;
        }
        
        private function getCity($city) {
            $pattern = "/(([а-я]*)(-[а-я]+)?(-[а-я]+)?)/ui";
            if (null === $city) return null;
            else $city = $city->getValue(); // Получаем значение ячейки, если она не пуста
            if ((true === is_string($city)) && 
                (1 === preg_match($pattern, trim(strip_tags($city)), $cityMatches))) {
                if ($city !== $cityMatches[0]) {
                    $city = null;
                }
            } else {
                $city = null;
            }
            return $city;
        }
        
        private function getArea($area) {
            $pattern = "/([а-я]*) ([а-я]*)(\.\)|\)|\.|)/ui";
            if (null === $area) return null;
            else $area = $area->getValue(); // Получаем значение ячейки, если она не пуста
            if ((true === is_string($area)) && 
                (1 === preg_match($pattern, trim(strip_tags($area)), $areaMatches))) {
                if ($area !== $areaMatches[0]) {
                    $area = null;
                }
            } else {
                $area = null;
            }
            return $area;
        }
        
        private function getLimit($limit) {
            $pattern = "/\d+-\d+/ui";
            if (null === $limit) return null;
            else $limit = $limit->getValue(); // Получаем значение ячейки, если она не пуста
            if ((true === is_string($limit)) && (1 === preg_match($pattern, trim(strip_tags($limit)), $limitMatches))) {
                if (($limit !== $limitMatches[0]) || ("0-0" === $limit)) {
                    $limit = null;
                }
            } else {
                $limit = null;
            }
            return $limit;
        }
        
        private function getZone($zone) {
            $pattern = "/\d+/ui";
            if (null === $zone) return null;
            else $zone = $zone->getValue(); // Получаем значение ячейки, если она не пуста
            if (true !== is_int($zone)) {
                if ((true === is_string($zone)) && (1 === preg_match($pattern, trim(strip_tags($zone)), $zoneMatches))) {
                    if ($zone !== $zoneMatches[0]) {
                        $zone = null;
                    }
                }
            }
            return $zone;
        }
        private function getCoeff($coeff) {
            $pattern = "/\d+(\.\d+)?/ui";
            if (null === $coeff) return null;
            else $coeff = $coeff->getValue(); // Получаем значение ячейки, если она не пуста
            if (true === is_string($coeff)) {
                $coeff = str_replace(',', '.', $coeff);
                if (1 === preg_match($pattern, trim(strip_tags($coeff)), $coeffMatches)) {
                    if ($coeff !== $coeffMatches[0]) {
                        $coeff = null;
                    } else {
                        $coeff = floatval($coeff);
                    }
                }
            }
            else if ((true !== is_float($coeff)) && (true !== is_int($coeff))) {
                $coeff = null;
            }
            return $coeff;
        }
    }

    class DBLoader {
        const DB_HOST = "calculator.ru";
        const DB_NAME = "calculator";
        const DB_USER = "calculatorUser";
        const DB_PASSWORD = "calculator.ru";
        private $sqlDB = null;
        private $result = null;
        private $baseCities = array();
        
        public function __construct() {
            $this->sqlDB = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASSWORD, self::DB_NAME);
            if (!$this->sqlDB->query("SET NAMES 'utf8'")) {
                $this->result["success"] = false;
                $this->result["connect"] = "Could not connect to DB";
            }
            else {
                $this->result["success"] = true;
                $this->result["connect"] = "Success connect to DB";
                // Выбираем все уникальные опорные города:
                $data = $this->sqlDB->query("SELECT DISTINCT Base_City AS all_base_cities FROM cities");
                if (false !== $data) {
                    for ($i = 0; $i < $data->num_rows; ++$i) {
                        $tmp = $data->fetch_row();
                        $baseCities[$i] = $tmp[0];
                    }
                    $this->result["base_cities"] = $baseCities;
                } else {
                    $this->result["base_cities"] = "error";
                }
            }
        }
      
        
        
        public function getResult() { return $this->result; }
    }
    
    if (isset($_POST["admin_loader"])) {
        $DB = new DBLoader();
        $result = $DB->getResult();
        $result["request"] = "correct";
        $excelFile = new ExcelFile($_FILES[0]["tmp_name"]);
        while (true === $excelFile->readNextPart());
        $result["baseCity"] = $excelFile->getBaseCity();
        $result["excelFormat"] = $excelFile->getResult();
        $result["numEntries"] = $excelFile->getNumEntries();
        //$result["FILES"] = $_FILES;
        echo json_encode($result);
    }
    /*if ( 0 < $_FILES;["file"]["error"] ) {
        echo 'Error: ' . $_FILES["file"]["error"] . "<br>";
    }
    else {
        move_uploaded_file($_FILES["file"]["tmp_name"], "tmpFiles/" . $_FILES["file"]["name"]);
    }*/

?>