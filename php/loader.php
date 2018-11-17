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

    class XlsxCities {

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
            // Чтение названия опорного города:
            $city = $this->getCity($cells->get('A'.'1'));
            $area = $this->getArea($cells->get('B'.'1'));
            if ((true !== $this->ckeckHeadline($cells)) || (null === $city) || (null === $area))
            {
                $this->_result = false;
            } else {
                $this->_baseCity = "$city ($area)";
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
            } else {
                return false;
            }
        }
        
        public function getCurPart() { return $this->_list; }
        
        public function getNumEntries() { return $this->_numEntries; }
        
        public function getBaseCity() { return $this->_baseCity; }
        
        public function getResult() { return $this->_result; }
        
        private function ckeckHeadline($cells) {
            $columns = range('A', 'G');
            $res = false;
            $checkValues = ["Направление", "Населенный пункт", "СТАНДАРТ", "ЭКСПРЕСС", 
                            "СУПЕРЭКСПРЕСС", "Зона доставки", "Коэффициент"];
            for ($index = 0; $index < count($columns); ++$index) {
                $tmp = $cells->get($columns[$index].'2');
                if ((null !== $tmp) && ($checkValues[$index] === $tmp->getValue())) {
                    $res = true;
                } else {
                    $res = false;
                    break;
                }
            }
            return $res;
        }
        
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
            $pattern = "/(([а-я]+)(-|\.|\. | |))*/ui";
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
                if ($limit !== $limitMatches[0]) {
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
            if (true === is_float($zone)) {
                $zone = intval($zone);
            } else if (true === is_string($zone)) {
                if (1 === preg_match($pattern, trim(strip_tags($zone)), $zoneMatches)) {
                    if ($zone === $zoneMatches[0]) {
                        $zone = intval($zone);
                    } else {
                        $zone = null;
                    }
                } else {
                    $zone = null;
                }
            } else if (true !== is_int($zone)) {
                $zone = null;
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
                    if ($coeff === $coeffMatches[0]) {
                        $coeff = floatval($coeff);
                    } else {
                        $coeff = null;
                    }
                } else {
                    $coeff = null;
                }
            } else if ((true !== is_float($coeff)) && (true !== is_int($coeff))) {
                $coeff = null;
            }
            return $coeff;
        }
    }

    class XlsxZones {
        
        private $_path = null;
        private $_numEntries = 0;
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
            $filter = new LoadFilter($this->_startRow, $this->_stepRows, range('A', 'E'));
            $reader->setReadFilter($filter);
            // Загружаем файл.
            $spreadsheet = $reader->load($this->_path);
            // Достаем объект Cells, имеющий доступ к содержимому ячеек
            $cells = $spreadsheet->getActiveSheet()->getCellCollection();
            // Проверяем заголовок таблицы:
            if (true !== $this->ckeckHeadline($cells))
            {
                $this->_result = false;
            }
            else {
                $this->_list = $this->readCells($cells, 2, $this->_startRow + $this->_stepRows);
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
                $filter = new LoadFilter($this->_startRow, $this->_stepRows, range('A', 'E'));
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
            } else {
                return false;
            }
        }
        
        public function getCurPart() { return $this->_list; }
        
        public function getNumEntries() { return $this->_numEntries; }
        
        public function getResult() { return $this->_result; }
        
        private function ckeckHeadline($cells) {
            $columns = range('A', 'E');
            $res = false;
            $checkValues = ["Номер зоны", "Режим доставки", "Стоимость 1", "Стоимость 2", 
                            "Стоимость 3"];
            for ($index = 0; $index < count($columns); ++$index) {
                $tmp = $cells->get($columns[$index].'1');
                if ((null !== $tmp) && ($checkValues[$index] === $tmp->getValue())) {
                    $res = true;
                } else {
                    $res = false;
                    break;
                }
            }
            return $res;
        }
        
        private function readCells($cells, $startRow, $endRow) {
            $tmpList = null;
            for ($row = $startRow, $index = 0; $row < $endRow; ++$row, ++$index) {
                $entry = null;
                // Читаем зону:
                $zone = $this->getZone($cells->get('A'.$row));
                $mode = $this->getMode($cells->get('B'.$row));
                $cost_1 = $this->getCost($cells->get('C'.$row));
                $cost_2 = $this->getCost($cells->get('D'.$row));
                $cost_3 = $this->getCost($cells->get('E'.$row));
                if ((null !== $zone) && (null !== $mode) && (null !== $cost_1) && 
                    (null !== $cost_2) && (null !== $cost_3)) {
                    $entry["zone"] = $zone;
                    $entry["mode"] = $mode;
                    $entry["cost_1"] = $cost_1;
                    $entry["cost_2"] = $cost_2;
                    $entry["cost_3"] = $cost_3;
                } else {
                    $this->_isEnd = true;
                    break;
                }
                $tmpList[$index] = $entry;
            }
            return $tmpList;
        }
        
        private function getZone($zone) {
            $pattern = "/\d+/ui";
            if (null === $zone) return null;
            else $zone = $zone->getValue(); // Получаем значение ячейки, если она не пуста
            if (true === is_float($zone)) {
                $zone = intval($zone);
            } else  if (true === is_string($zone)) {
                if (1 === preg_match($pattern, trim(strip_tags($zone)), $zoneMatches)) {
                    if ($zone === $zoneMatches[0]) {
                        $zone = intval($zone);
                    } else {
                        $zone = null;
                    }
                } else {
                    $zone = null;
                }
            } else if (true !== is_int($zone)) {
                $zone = null;
            }
            return $zone;
        }
        
        private function getMode($mode) {
            $modesRu = ["Стандарт", "Экспресс", "Суперэкспресс"];
            $modesEn = ["standart", "express", "superexpress"];
            $isFound = false;
            if (null === $mode) return null;
            else $mode = $mode->getValue(); // Получаем значение ячейки, если она не пуста
            if (false === is_string($mode)) return null;
            for ($i = 0, $isFound = false; $i < count($modesRu); ++$i) {
                if ($mode === $modesRu[$i]) {
                    $mode = $modesEn[$i];
                    $isFound = true;
                    break;
                }
            }
            // Если совпадений не найдено:
            if (false === $isFound) {
                $mode = null;
            }
            return $mode;
        }
        
        private function getCost($cost) {
            $pattern = "/\d+(\.\d+)?/ui";
            if (null === $cost) return null;
            else $cost = $cost->getValue(); // Получаем значение ячейки, если она не пуста
            if (true === is_string($cost)) {
                $cost = str_replace(',', '.', $cost);
                if (1 === preg_match($pattern, trim(strip_tags($cost)), $costMatches)) {
                    if ($cost !== $costMatches[0]) {
                        $cost = null;
                    } else {
                        $cost = floatval($cost);
                    }
                } else {
                    $cost = null;
                }
            } else if ((true !== is_float($cost)) && (true !== is_int($cost))) {
                $cost = null;
            }
            return $cost;
        }
        
    }

    class ZoneEntry {
        
        private $_zone = null;
        private $_modes = array();
        private $_count = 0;
        
        public function __construct($zone) {
            if ((null !== $zone) && (true === is_int($zone))) {
                $this->_zone = $zone;
            } else {
                throw new Exception("[Zone entry]: __construct(): Not correct input data for creating object");
            }
        }
        
        public function addMode($mode, $cost_1, $cost_2, $cost_3) {
            if ((null !== $mode) && (true === is_string($mode)) && 
                (null !== $cost_1) && ((true === is_int($cost_1)) || (true === is_float($cost_1))) && 
                (null !== $cost_2) && ((true === is_int($cost_2)) || (true === is_float($cost_1))) && 
                (null !== $cost_3) && ((true === is_int($cost_3)) || (true === is_float($cost_1)))) {
                for ($index = 0; $index < count($this->_modes); ++$index) {
                    if ($this->_modes[$index] === $mode) {
                        throw new Exception("[Zone entry]: addMode(): This mode already exists in object");
                    }
                }
                $this->_modes[$this->_count]["mode"] = $mode;
                $this->_modes[$this->_count]["cost_1"] = $cost_1;
                $this->_modes[$this->_count]["cost_2"] = $cost_2;
                $this->_modes[$this->_count]["cost_3"] = $cost_3;
                ++$this->_count;
            } else {
                throw new Exception("[Zone entry]: addMode(): Not correct input data");
            }
        }
        
        public function getZone() { return $this->_zone; }
        
        public function getModes() {
            $tmpModes = array();
            for ($i = 0; $i < count($this->_modes); ++$i) {
                $tmpModes[$i] = $this->_modes[$i]["mode"];
            }
            return $tmpModes;
        }
        
        public function getCost_1($mode) {
            $cost_1 = null;
            for ($i = 0; $i < count($this->_modes); ++$i) {
                if ($this->_modes[$i]["mode"] === $mode) {
                    $cost_1 = $this->_modes[$i]["cost_1"];
                    break;
                }
            }
            return $cost_1;
        }
        
        public function getCost_2($mode) {
            $cost_2 = null;
            for ($i = 0; $i < count($this->_modes); ++$i) {
                if ($this->_modes[$i]["mode"] === $mode) {
                    $cost_2 = $this->_modes[$i]["cost_2"];
                    break;
                }
            }
            return $cost_2;
        }
        
        public function getCost_3($mode) {
            $cost_3 = null;
            for ($i = 0; $i < count($this->_modes); ++$i) {
                if ($this->_modes[$i]["mode"] === $mode) {
                    $cost_3 = $this->_modes[$i]["cost_3"];
                    break;
                }
            }
            return $cost_3;
        }
    }

    class ZoneStorage {
        
        private $_zones = array();
        private $_count = 0;
        
        public function insertList($list) {
            $res["result"] = false;
            $isFound = false;
            $id = -1;
            try {
                for ($index = 0; $index < count($list); ++$index) {
                    // Поиск объекта с таким же номером зоны:
                    for ($f = 0, $isFound = false, $id = -1; $f < count($this->_zones);++$f) {
                        if ($this->_zones[$f]->getZone() === $list[$index]["zone"]) {
                            $id = $f;
                            $isFound = true;
                            break;
                        }
                    }
                    // Проверяем, найден ли объект с указанным номером зоны:
                    if (true === $isFound) {
                        $this->_zones[$id]->addMode($list[$index]["mode"], 
                                                    $list[$index]["cost_1"], 
                                                    $list[$index]["cost_2"], 
                                                    $list[$index]["cost_3"]);
                    } else {
                        // Создаем объект для нового номера зоны:
                        $this->_zones[$this->_count] = new ZoneEntry($list[$index]["zone"]);
                        $this->_zones[$this->_count]->addMode($list[$index]["mode"], 
                                                              $list[$index]["cost_1"], 
                                                              $list[$index]["cost_2"], 
                                                              $list[$index]["cost_3"]);
                        // Инкрементируем счетчик:
                        ++$this->_count;
                    }
                }
                $res["result"] = true;
            } catch (Exception $e) {
                $res["result"] = false;
                $res["errMsg"] = "[ZoneStorage]: insertList(): Exception for ZoneEntry object: $e";
            }
            return $res;
        }
        
        
        public function getZones() { return $this->_zones; }
    }

    class Validator {
        const USER_LOGIN = "delserver";
        const USER_PASSWORD = "~doeirkghjf#938";
        const DB_HOST = "calculator.ru";
        const DB_NAME = "calculator";
        const DB_USER = "delserver123_497";
        const DB_PASSWORD = "restlfk309";
        private $_result = false;
        
        public function __construct($userLogin, $userPassword, $dbHost, $dbName, $dbUser, $dbPassword) {
            if ((trim(strip_tags($userLogin)) === self::USER_LOGIN) && 
                (trim(strip_tags($userPassword)) === self::USER_PASSWORD) && (trim(strip_tags($dbHost)) === self::DB_HOST) && 
                (trim(strip_tags($dbName)) === self::DB_NAME) && (trim(strip_tags($dbUser)) === self::DB_USER) && 
                (trim(strip_tags($dbPassword)) === self::DB_PASSWORD)) {
                $this->_result = true;
            } else {
                $this->_result = false;
            }
        }
        
        public function getResult() { return $this->_result; }
        
        public function getDBHost() {
            if (true === $this->getResult()) return self::DB_HOST;
            else return false;
        }
        
        public function getDBName() {
            if (true === $this->getResult()) return self::DB_NAME;
            else return false;
        }
        
        public function getDBUser() {
            if (true === $this->getResult()) return self::DB_USER;
            else return false;
        }
        
        public function getDBPassword() {
            if (true === $this->getResult()) return self::DB_PASSWORD;
            else return false;
        }
    }


    class DBCitiesLoader {
        private $_sqlDB = null;
        private $_result = null;
        private $_baseCities = array();
        
        public function __construct($valid) {
            $this->_sqlDB = new mysqli($valid->getDBHost(), $valid->getDBUser(), 
                                      $valid->getDBPassword(), $valid->getDBName());
            if (!$this->_sqlDB->query("SET NAMES 'utf8'")) {
                $this->_result["connect"] = false;
            }
            else {
                $this->_result["connect"] = true;
                // Выбираем все уникальные опорные города:
                $data = $this->_sqlDB->query("SELECT DISTINCT Base_City AS all_base_cities FROM cities");
                if (false !== $data) {
                    for ($i = 0; $i < $data->num_rows; ++$i) {
                        $tmp = $data->fetch_row();
                        $this->_baseCities[$i] = $tmp[0];
                    }
                    $this->_result["isEntries"] = true;
                } else {
                    $this->_result["isEntries"] = false;
                }
            }
        }
      
        public function deleteEntries($baseCity) {
            $res["result"] = false;
            $res["editEntries"] = null;
            $isFound = false;
            if (true === $this->_result["connect"]) {
                if (true === $this->_result["isEntries"]) {
                    for ($i = 0; $i < count($this->_baseCities); ++$i) {
                        if ($baseCity === $this->_baseCities[$i]) {
                            $isFound = true;
                            break;
                        }
                    }
                    if (true === $isFound) {
                        // Экранируем $baseCity для БД:
                        $baseCity = $this->_sqlDB->real_escape_string($baseCity);
                        $dbRes = $this->_sqlDB->query("DELETE FROM cities WHERE Base_City='$baseCity'");
                        if (false === $dbRes) {
                            $res["errMsg"] = "Not correct delete entries with $baseCity";
                            $res["result"] = false;
                        } else {
                            // Отменяем изменения, которые могли быть внесены после добавления записей с указанным опорным городом (например, пересечение записей с другими опорным городами - СПБ -> Москва и Москва -> СПБ):
                            for ($i = 0, $res["result"] = true; $i < count($this->_baseCities); ++$i) {
                                $tmpCity = $this->_baseCities[$i];
                                // Экранируем $tmpCity для БД:
                                $tmpCity = $this->_sqlDB->real_escape_string($tmpCity);
                                $tmpRes = $this->_sqlDB->query("SELECT * FROM cities WHERE Base_City='$tmpCity' AND City='$baseCity' AND Direction='TO'");
                                if (false === $tmpRes) {
                                    $res["errMsg"] = "Query is not correct";
                                    $res["result"] = false;
                                    break;
                                }
                                if (0 === $tmpRes->num_rows) {
                                    continue;
                                }
                                if (1 === $tmpRes->num_rows) {
                                    $tmpRes = $this->_sqlDB->query("UPDATE cities SET Direction='BOTH' WHERE Base_City='$tmpCity' AND City='$baseCity' AND Direction='TO'");
                                    if (false === $tmpRes) {
                                        $res["errMsg"] = "Not correct update entry with $tmpCity - $baseCity";
                                        $res["result"] = false;
                                        break;
                                    } else {
                                        $res["editEntries"][$i] = "Update data to entry $baseCity - $tmpCity (set BOTH)";
                                    }
                                } else {
                                    $res["errMsg"] = "Not correct select for $tmpCity - $baseCity (few entries)";
                                    $res["result"] = false;
                                    break;
                                }
                            }
                        }
                    } else {
                        $res["result"] = true;
                    }
                } else {
                    $res["result"] = true;
                }
            } else {
                $res["errMsg"] = "Not success result in object";
                $res["result"] = false;
            }
            return $res;
        }
        
        public function deleteAllEntries() {
            $res = false;
            if (true === $this->_result["connect"]) {
                if (true === $this->_result["isEntries"]) {
                    $res = $this->_sqlDB->query("DELETE FROM cities");
                } else {
                    $res = true;
                }
            } else {
                $res = false;
            }
            return $res;
        }
        
        public function insertEntries($baseCity, $list) {
            $res["result"] = false;
            $res["numEntries"] = 0;
            if (true === $this->_result["connect"]) {
                // Экранируем $baseCity для БД:
                $baseCity = $this->_sqlDB->real_escape_string($baseCity);
                for ($index = 0, $res["result"] = true; $index < count($list); ++$index) {
                    // Проверяем, что нет такой комбинации в таблице БД:
                    $tmpCity = $list[$index]["city"];
                    // Экранируем $tmpCity для БД:
                    $tmpCity = $this->_sqlDB->real_escape_string($tmpCity);
                    $dbRes = $this->_sqlDB->query("SELECT * FROM cities WHERE Base_City='$baseCity' AND City='$tmpCity'");
                    if (false === $dbRes) {
                        $res["errMsg"] = "Query is not correct";
                        $res["result"] = false;
                        break;
                    }
                    if (0 === $dbRes->num_rows) {
                        // Формируем данные для добавления в БД:
                        $zone = $list[$index]["zone"];
                        $coeff = $list[$index]["coeff"];
                        $standart = $list[$index]["mode"]["standart"];
                        $tmpData = "[$zone][$coeff]{standart:[$standart]}";
                        if (null !== $list[$index]["mode"]["express"]) {
                            $express = $list[$index]["mode"]["express"];
                            $tmpData = "$tmpData{express:[$express]}";
                        }
                        if (null !== $list[$index]["mode"]["superexpress"]) {
                            $superexpress = $list[$index]["mode"]["superexpress"];
                            $tmpData = "$tmpData{superexpress:[$superexpress]}";
                        }
                        // Экранируем данные для добавления в БД:
                        $tmpData = $this->_sqlDB->real_escape_string($tmpData);
                        // Добавляем запись в таблицу БД:
                        $dbRes = $this->_sqlDB->query("INSERT INTO cities (Base_City, City, Direction, Data) VALUES ('$baseCity', '$tmpCity', 'BOTH', '$tmpData')");
                        if (true === $dbRes) {
                            // Успешная запись:
                            ++$res["numEntries"];
                        } else {
                            $debug = "INSERT INTO cities (Base_City, City, Direction, Data) VALUES ('$baseCity', '$tmpCity', 'BOTH', '$tmpData')";
                            $res["errMsg"] = "Query is not correct: $debug";
                            $res["result"] = false;
                            break;
                        }
                    } else {
                        $res["errMsg"] = "Found second entry in DB with: $baseCity - $tmpCity";
                        $res["result"] = false;
                        break;
                    }
                }
            } else {
                $res["errMsg"] = "Not success result in object";
                $res["result"] = false;
            }
            return $res;
        }
        
        public function correctCitiesTable($baseCity) {
            $res["result"] = false;
            $res["editEntries"] = null;
            if (true === $this->_result["connect"]) {
                // Экранируем $baseCity для БД:
                $baseCity = $this->_sqlDB->real_escape_string($baseCity);
                for ($index = 0, $res["result"] = true; $index < count($this->_baseCities); ++$index) {
                    $tmpCity = $this->_baseCities[$index];
                    // Экранируем $tmpCity для БД:
                    $tmpCity = $this->_sqlDB->real_escape_string($tmpCity);
                    //  Проверяем, есть ли в записанных строках комбинация: $baseCity - $tmpCity:
                    $dbRes = $this->_sqlDB->query("SELECT * FROM cities WHERE Base_City='$baseCity' AND City='$tmpCity'");
                    if (false === $dbRes) {
                        $res["errMsg"] = "Query is not correct";
                        $res["result"] = false;
                        break;
                    }
                    if (0 === $dbRes->num_rows){
                        continue;
                    } else {
                        if (1 !== $dbRes->num_rows) {
                            $res["errMsg"] = "Few entries with values: $baseCity - $tmpCity";
                            $res["result"] = false;
                            break;
                        }
                        // Если есть комбинация $baseCity - $tmpCity, то надо проверить наличие комбинации $tmpCity - $baseCity:
                        $dbRes = $this->_sqlDB->query("SELECT * FROM cities WHERE Base_City='$tmpCity' AND City='$baseCity'");
                        if (false === $dbRes) {
                            $res["errMsg"] = "Query is not correct";
                            $res["result"] = false;
                            break;
                        }
                        if (0 === $dbRes->num_rows) {
                            // Если нет, то поле Direction='BOTH' для комбинации: $baseCity - $tmpCity:
                            $dbRes = $this->_sqlDB->query("UPDATE cities SET Direction='BOTH' WHERE Base_City='$baseCity' AND City='$tmpCity'");
                            if (false === $dbRes) {
                                $res["errMsg"] = "Can not update data in DB for: $baseCity - $tmpCity (set BOTH)";
                                $res["result"] = false;
                                break;
                            } else {
                                $res["editEntries"][$index] = "Update data to entry $baseCity - $tmpCity (set BOTH)";
                            }
                        } else {
                            if (1 !== $dbRes->num_rows) {
                                $res["errMsg"] = "Few entries with values: $tmpCity - $baseCity";
                                $res["result"] = false;
                                break;
                            }
                            // Если есть комбинация $tmpCity - $baseCity, требуется поставить поле Direction='TO' для этих строк:
                            $dbRes_1 = $this->_sqlDB->query("UPDATE cities SET Direction='TO' WHERE Base_City='$baseCity' AND City='$tmpCity'");
                            $dbRes_2 = $this->_sqlDB->query("UPDATE cities SET Direction='TO' WHERE Base_City='$tmpCity' AND City='$baseCity'");
                            // Проверяем результат:
                            if (false === $dbRes_1) {
                                $res["errMsg"] = "Can not update data in DB for: $baseCity - $tmpCity (set TO)";
                                $res["result"] = false;
                                break;
                            } else {
                                $res["editEntries"][$index] = "Update data to entry $baseCity - $tmpCity (set TO)";
                            }
                            // Проверяем результат:
                            if (false === $dbRes_2) {
                                $res["errMsg"] = "Can not update data in DB for: $tmpCity - $baseCity (set TO)";
                                $res["result"] = false;
                                break;
                            } else {
                                $res["editEntries"][$index] = "Update data to entry $tmpCity - $baseCity (set TO)";
                            }
                        }
                    }
                }
            } else {
                $res["errMsg"] = "Not success result in object";
                $res["result"] = false;
            }
            return $res;
        }
        
        public function getConnectResult() { return $this->_result["connect"]; }
    }

    class DBZonesLoader {
        private $_sqlDB = null;
        private $_result = null;
        
        public function __construct($valid) {
            $this->_sqlDB = new mysqli($valid->getDBHost(), $valid->getDBUser(), 
                                      $valid->getDBPassword(), $valid->getDBName());
            if (!$this->_sqlDB->query("SET NAMES 'utf8'")) {
                $this->_result["connect"] = false;
            }
            else {
                $this->_result["connect"] = true;
            }
        }
        
        public function insertZones($zones) {
            $res["result"] = false;
            $res["numEntries"] = 0;
            if (true === $this->_result["connect"]) {
                if (true === $this->deleteAllEntries()) {
                    for ($index = 0; $index < count($zones); ++$index) {
                        $tmpModes = $zones[$index]->getModes();
                        // Экранируем $tmpZone для БД:
                        $tmpZone = $this->_sqlDB->real_escape_string($zones[$index]->getZone());
                        for ($f = 0, $res["result"] = true; $f < count($tmpModes); ++$f) {
                            // Экранируем $tmpMode для БД:
                            $tmpMode = $this->_sqlDB->real_escape_string($tmpModes[$f]);
                            $tmpCost_1 = $zones[$index]->getCost_1($tmpModes[$f]);
                            $tmpCost_2 = $zones[$index]->getCost_2($tmpModes[$f]);
                            $tmpCost_3 = $zones[$index]->getCost_3($tmpModes[$f]);
                            $tmpData = "{" . "$tmpCost_1|$tmpCost_2|$tmpCost_3" . "}";
                            // Экранируем $tmpData для БД:
                            $tmpData = $this->_sqlDB->real_escape_string($tmpData);
                            $dbRes = $this->_sqlDB->query("INSERT INTO zones (Zone, Delivery_Type, Data) VALUES ('$tmpZone', '$tmpMode', '$tmpData')");
                            if (true === $dbRes) {
                                // Успешная запись:
                                ++$res["numEntries"];
                            } else {
                                $debug = "INSERT INTO zones (Zone, Delivery_Type, Data) VALUES ('$tmpZone', '$tmpMode', '$tmpData')";
                                $res["errMsg"] = "Query is not correct: $debug";
                                $res["result"] = false;
                                break;
                            }
                        }
                    }
                } else {
                    $res["errMsg"] = "[DBZonesLoader]: insertZones(): Not success deleteAllEntries()";
                    $res["result"] = false;
                }
            } else {
                $res["errMsg"] = "Not success result in object";
                $res["result"] = false;
            }
            return $res;
        }
        
        public function deleteAllEntries() {
            $res = false;
            if (true === $this->_result["connect"]) {
                $res = $this->_sqlDB->query("DELETE FROM zones");
            } else {
                $res = false;
            }
            return $res;
        }
        
        public function getConnectResult() { return $this->_result["connect"]; }
    }
    
    if ((!isset($_POST["userLogin"])) || (!isset($_POST["userLogin"])) || 
        (!isset($_POST["userLogin"])) || (!isset($_POST["userLogin"])) || 
        (!isset($_POST["userLogin"])) || (!isset($_POST["userLogin"]))) {
        $result["success"] = false;
        echo json_encode($result);
    } else if (isset($_POST["excelCities_loader"])) {
        $result = null;
        // Проверка данных для доступа:
        $validator = new Validator($_POST["userLogin"], $_POST["userPassword"], 
                                   $_POST["dbHost"], $_POST["dbName"], 
                                   $_POST["dbUser"], $_POST["dbPassword"]);
        if (true === $validator->getResult()) {
            // Создаем объект БД для таблицы Cities:
            $DB = new DBCitiesLoader($validator);
            if (true === $DB->getConnectResult()) {
                for ($index = 0, $result["success"] = true; ($index < count($_FILES)) && (true === $result["success"]); ++$index) {
                    $fileName = $_FILES[$index]["name"];
                    $tmpFileName = $_FILES[$index]["tmp_name"];
                    // Читаем файл:
                    $excelFile = new XlsxCities($tmpFileName);
                    if (true === $excelFile->getResult()) {
                        $tmpRes = $DB->deleteEntries($excelFile->getBaseCity());
                        if (true === $tmpRes["result"]) {
                            $result["filesInfo"][$index]["fileName"] = $fileName;
                            $result["filesInfo"][$index]["group_1"] = $tmpRes["editEntries"];
                            $result["filesInfo"][$index]["numEntries"] = 0;
                            $result["filesInfo"][$index]["success"] = true;
                            // Пока успешно читается следующий кусок файла:
                            do {
                                // Добавляем список записей в БД:
                                $tmpRes = $DB->insertEntries($excelFile->getBaseCity(), $excelFile->getCurPart());
                                if (true === $tmpRes["result"]) {
                                    $result["filesInfo"][$index]["numEntries"] += $tmpRes["numEntries"];
                                } else {
                                    $err = $tmpRes["errMsg"];
                                    $result["filesInfo"][$index]["success"] = false;
                                    $result["error"] = "[ERROR]: $fileName: Insert operation is not success: $err";
                                    $result["success"] = false;
                                    break;
                                }
                            } while (true === $excelFile->readNextPart());
                            if (true === $result["success"]) {
                                // Коррекция строк в таблице БД:
                                $tmpRes = $DB->correctCitiesTable($excelFile->getBaseCity());
                                if (true === $tmpRes["result"]) {
                                    $result["filesInfo"][$index]["group_2"] = $tmpRes["editEntries"];
                                } else {
                                    $err = $tmpRes["errMsg"];
                                    $result["filesInfo"][$index]["success"] = false;
                                    $result["error"] = "[ERROR]: $fileName: Correct operation is not success: $err";
                                    $result["success"] = false;
                                    break;
                                }
                            }
                        } else {
                            $err = $tmpRes["errMsg"];
                            $result["error"] = "[ERROR]: Delete operation is not success: $err";
                            $result["success"] = false;
                        }
                    } else {
                        $result["error"] = "[ERROR]: Read operation is not success for file [$fileName]";
                        $result["success"] = false;
                    }
                }
            } else {
                $result["error"] = "[ERROR]: Connect operation is not success";
                $result["success"] = false;
            }
        } else {
            $result["error"] = "[ERROR]: Access closed";
            $result["success"] = false;
        }
        echo json_encode($result);
    } else if (isset($_POST["excelCities_delete"])) {
        $result = null;
        // Проверка данных для доступа:
        $validator = new Validator($_POST["userLogin"], $_POST["userPassword"], 
                                   $_POST["dbHost"], $_POST["dbName"], 
                                   $_POST["dbUser"], $_POST["dbPassword"]);
        if (true === $validator->getResult()) {
            // Создаем объект БД:
            $DB = new DBCitiesLoader($validator);
             if (true === $DB->getConnectResult()) {
                 // Удаляем все записи:
                 $dbRes = $DB->deleteAllEntries();
                 // Проверка результата:
                 if (true === $dbRes) {
                     $result["success"] = true;
                 } else {
                    $result["error"] = "[ERROR]: Delete operation is not success";
                    $result["success"] = false;
                 }
             } else {
                $result["error"] = "[ERROR]: Connect operation is not success";
                $result["success"] = false;
             }
        } else {
            $result["error"] = "[ERROR]: Access closed";
            $result["success"] = false;
        }
        echo json_encode($result);
    } else if ((isset($_POST["excelZones_loader"])) && (1 === count($_FILES))) {
        $result = null;
        // Проверка данных для доступа:
        $validator = new Validator($_POST["userLogin"], $_POST["userPassword"], 
                                   $_POST["dbHost"], $_POST["dbName"], 
                                   $_POST["dbUser"], $_POST["dbPassword"]);
        if (true === $validator->getResult()) {
            $DB = new DBZonesLoader($validator);
            if (true === $DB->getConnectResult()) {
                $fileName = $_FILES[0]["name"];
                $tmpFileName = $_FILES[0]["tmp_name"];
                // Создаем объект чтения файла:
                $excelFile = new XlsxZones($tmpFileName);
                if (true === $excelFile->getResult()) {
                    $result["success"] = true;
                    $result["fileName"] = $fileName;
                    $result["numEntries"] = 0;
                    $zoneStorage = new ZoneStorage();
                    do {
                        // Добавляем данные из файла в хранилище:
                        $tmpRes = $zoneStorage->insertList($excelFile->getCurPart());
                        if (true !== $tmpRes["result"]) {
                            $err = $tmpRes["errMsg"];
                            $result["error"] = "[ERROR]: $fileName: Insert operation (in storage) is not success: $err";
                            $result["success"] = false;
                            break;
                        }
                    } while (true === $excelFile->readNextPart());
                    if (true === $result["success"]) {
                        // Добавляем данные из хранилища в БД:
                        $tmpRes = $DB->insertZones($zoneStorage->getZones());
                        if (true === $tmpRes["result"]) {
                            $result["numEntries"] += $tmpRes["numEntries"];
                        } else {
                            $err = $tmpRes["errMsg"];
                            $result["error"] = "[ERROR]: $fileName: Insert operation (in DB) is not success: $err";
                            $result["success"] = false;
                        }
                    }
                } else {
                    $result["error"] = "[ERROR]: Read operation is not success for file [$fileName]";
                    $result["success"] = false;
                }
             } else {
                $result["error"] = "[ERROR]: Connect operation is not success";
                $result["success"] = false;
             }
        } else {
            $result["error"] = "[ERROR]: Access closed";
            $result["success"] = false;
        }
        echo json_encode($result);
    } else if (isset($_POST["excelZones_delete"])) {
        $result = null;
        // Проверка данных для доступа:
        $validator = new Validator($_POST["userLogin"], $_POST["userPassword"], 
                                   $_POST["dbHost"], $_POST["dbName"], 
                                   $_POST["dbUser"], $_POST["dbPassword"]);
        if (true === $validator->getResult()) {
            // Создаем объект БД:
            $DB = new DBZonesLoader($validator);
             if (true === $DB->getConnectResult()) {
                 // Удаляем все записи:
                 $dbRes = $DB->deleteAllEntries();
                 // Проверка результата:
                 if (true === $dbRes) {
                     $result["success"] = true;
                 } else {
                    $result["error"] = "[ERROR]: Delete operation is not success";
                    $result["success"] = false;
                 }
             } else {
                $result["error"] = "[ERROR]: Connect operation is not success";
                $result["success"] = false;
             }
        } else {
            $result["error"] = "[ERROR]: Access closed";
            $result["success"] = false;
        }
        echo json_encode($result);
    } else {
        $result["success"] = false;
        echo json_encode($result);
    }

?>