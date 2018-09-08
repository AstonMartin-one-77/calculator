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
        echo json_encode($result);
    }
    /*if ( 0 < $_FILES["file"]["error"] ) {
        echo 'Error: ' . $_FILES["file"]["error"] . "<br>";
    }
    else {
        move_uploaded_file($_FILES["file"]["tmp_name"], "tmpFiles/" . $_FILES["file"]["name"]);
    }*/

?>