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
			if ((1 == $row) || 
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
            if (("Направление" != $cells->get('A'.'2')->getValue()) || 
                ("Населенный пункт" != $cells->get('B'.'2')->getValue()) || 
                ("СТАНДАРТ" != $cells->get('C'.'2')->getValue()) || 
                ("ЭКСПРЕСС" != $cells->get('D'.'2')->getValue()) || 
                ("СУПЕРЭКСПРЕСС" != $cells->get('E'.'2')->getValue()) || 
                ("Зона доставки" != $cells->get('F'.'2')->getValue()) || 
                ("Коэффициент" != $cells->get('G'.'2')->getValue()))
            {
                $this->_result = false;
            }
            else {
                $this->_list = $this->readCells($cells, 3, $this->_startRow + $this->_stepRows);
                if ((null != $this->_list) && (0 < count($this->_list))) {
                    $this->_startRow = $this->_startRow + $this->_stepRows;
                    $this->_numEntries += count($this->_list);
                    $this->_result = true;
                } else {
                    $this->_result = false;
                }
            }
        }
        
        public function readNextPart() {
            if (true == $this->_result) {
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
                if ((null != $this->_list) && (0 < count($this->_list))) {
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
        
        public function getCurPart() {
            return $this->_list;
        }
        
        public function getBaseCity() { return $this->_baseCity; }
        
        public function getResult() { return $this->_result; }
        
        private function readCells($cells, $startRow, $endRow) {
            $tmpList = null;
            for ($row = $startRow; $row < $endRow; ++$row) {
                $entry = null;
                // Читаем край/область и город доставки:
                $area = $this->getArea($cells->get('A'.$row)->getValue());
                $city = $this->getCity($cells->get('B'.$row)->getValue());
                if ((null != $city) && (null != $area)) {
                    $entry["city"] = "$city ($area)";
                } else {
                    break;
                }
                $standart = $this->getLimit($cells->get('C'.$row)->getValue());
                if (null != $standart) {
                    $entry["mode"]["standart"] = $standart;
                } else {
                    break;
                }
                $express = $this->getLimit($cells->get('D'.$row)->getValue());
                if (null != $express) {
                    $entry["mode"]["express"] = $express;
                }
                $spExpress = $this->getLimit($cells->get('E'.$row)->getValue());
                if (null != $spExpress) {
                    $entry["mode"]["superexpress"] = $spExpress;
                }
                $zone = $this->getZone($cells->get('E'.$row)->getValue());
                if (null != $zone) {
                    $entry["zone"] = $zone;
                } else {
                    break;
                }
                $coeff = $this->getCoeff($cells->get('E'.$row)->getValue());
                if (null != $coeff) {
                    $entry["coeff"] = $coeff;
                } else {
                    break;
                }
                $tmpList[$row] = $entry;
            }
            return $tmpList;
        }
        
        private function getCity($city) {
            $pattern = "/(([а-я]*)(-[а-я]+)?(-[а-я]+)?)/ui";
            // TODO: Добавить защиту от взлома БД.
            return $city;
        }
        
        private function getArea($area) {
            // TODO: Добавить защиту от взлома БД.
            return $area;
        }
        
        private function getLimit($limit) {
            // TODO: Добавить проверку на формат.
            if (("0-0" != $limit) && ("" != $limit)) {
                return $limit;
            } else {
                return null;
            }
        }
        
        private function getZone($zone) {
            // TODO:
            return (integer) $zone;
        }
        private function getCoeff($coeff) {
            // TODO:
            return (float) $coeff;
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
        $result["baseCity"] = $excelFile->getBaseCity();
        $result["excelFormat"] = $excelFile->getResult();
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