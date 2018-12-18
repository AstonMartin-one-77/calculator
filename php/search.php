<?php 

    class calculatorDB {

        const DB_HOST = "calculator.ru";
        const DB_NAME = "calculator";
        const DB_USER = "calculatorUser";
        const DB_PASSWORD = "calculator.ru";
        private $_sqlDB = null;
        private $_result = null;
        
        function __construct() {
            $this->_sqlDB = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASSWORD, self::DB_NAME);
            if (!$this->_sqlDB->query("SET NAMES 'utf8'")) {
                $this->_result["connect"] = false;
            }
            else {
                $this->_result["connect"] = true;
            }
        }
      
        function getBaseCityList($userString) {
            $pattern = "/(([а-я0-9]+)(-|\.|\. | |))*(\((([а-я0-9]+)(-|\.|\. | |))+\))?/ui";
            $res["result"] = false;
            $res["errMsg"] = null;
            $res["forUserMsg"] = null;
            $res["num_rows"] = 0;
            $res["cities"] = null;

            if ((true === $this->_result["connect"]) && (true === is_string($userString)) && 
                // Проверяем на паттерн, предварительно удалив все лишние символы из запроса.
                (1 === preg_match($pattern, trim(strip_tags($userString)), $matches))) {
                // Если найдено совпадение в запросе по паттерну, копируем результат.
                $userString = $matches[0];
                // Экранируем $userString для БД:
                $userString = $this->_sqlDB->real_escape_string($userString);
                /** Поиск в БД на совпадение городам. */
                $data = $this->_sqlDB->query("SELECT DISTINCT Base_City AS all_city FROM cities WHERE Base_City LIKE '$userString%' UNION SELECT DISTINCT City AS all_city FROM cities WHERE City LIKE '$userString%'");
                /** Проверяем результат. */
                if (false === $data) {
                    $res["errMsg"] = "Query is not correct";
                    $res["result"] = false;
                } else {
                    $res["result"] = true;
                    $res["num_rows"] = $data->num_rows;
                    $baseCities = array();
                    for ($i = 0; $i < $data->num_rows; ++$i) {
                        $tmp = $data->fetch_row();
                        $baseCities[$i] = $tmp[0];
                    }
                    $res["cities"] = $baseCities;
                }
            } else {
                $res["result"] = false;
            }
            return $res;
        }
      
        function getCityList($baseCity, $userString) {
            $pattern = "/(([а-я0-9]+)(-|\.|\. | |))*(\((([а-я0-9]+)(-|\.|\. | |))+\))?/ui";
            $res["result"] = false;
            $res["errMsg"] = null;
            $res["num_rows"] = 0;
            $res["cities"] = null;
            
            if ((true === $this->_result["connect"]) && 
                (true === is_string($baseCity)) && 
                (true === is_string($userString)) && 
                /** Проверяем на паттерн, предварительно удалив все лишние символы из запроса. */
                (1 === preg_match($pattern, trim(strip_tags($baseCity)), $baseMatches)) && 
                (1 === preg_match($pattern, trim(strip_tags($userString)), $matches))) {
                /** Если найдено совпадение в запросе по паттерну, копируем результат. */
                $baseCity = $baseMatches[0];
                $userString = $matches[0];
                // Экранируем $baseCity и $userString для БД:
                $baseCity = $this->_sqlDB->real_escape_string($baseCity);
                $userString = $this->_sqlDB->real_escape_string($userString);
                /** Поиск в БД на совпадение по городам. */
                $data = $this->_sqlDB->query("SELECT DISTINCT Base_City AS all_city FROM cities WHERE City='$baseCity' AND Base_City LIKE '$userString%' 
                                            UNION SELECT DISTINCT City AS all_city FROM cities WHERE Base_City='$baseCity' AND City LIKE '$userString%'");
                /** Проверяем результат. */
                if (false === $data) {
                    $res["errMsg"] = "Query is not correct";
                    $res["result"] = false;
                } else {
                    $res["result"] = true;
                    $res["num_rows"] = $data->num_rows;
                    $baseCities = array();
                    for ($i = 0; $i < $data->num_rows; ++$i) {
                        $tmp = $data->fetch_row();
                        $baseCities[$i] = $tmp[0];
                    }
                    $res["cities"] = $baseCities;
                }
            } else {
                $res["result"] = false;
            }
            return $res;
        }
      
        function getData($fromCity, $toCity) {
            $pattern = "/(([а-я0-9]+)(-|\.|\. | |))*(\((([а-я0-9]+)(-|\.|\. | |))+\))?/ui";
            $res["result"] = false;
            $res["errMsg"] = null;
            $res["forUserMsg"] = null;
            $res["DATA"] = null;
            
            // Формируемые данные (инициализируем стандартными данными):
            $zone = 0;
            $coeff = 1;
            $modeDates = array();
            $modeRates = array();
            // Первая часть:
            if ((true === $this->_result["connect"]) && 
                (true === is_string($fromCity)) && 
                (true === is_string($toCity)) && 
                // Проверяем на паттерн, предварительно удалив все лишние символы из запроса.
                (1 === preg_match($pattern, trim(strip_tags($fromCity)), $fromCityMatches)) && 
                (1 === preg_match($pattern, trim(strip_tags($toCity)), $toCityMatches))) {
                // Если найдено совпадение в запросе по паттерну, копируем результат:
                $fromCity = $fromCityMatches[0];
                $toCity = $toCityMatches[0];
                // Экранируем $fromCity и $toCity для БД:
                $fromCity = $this->_sqlDB->real_escape_string($fromCity);
                $toCity = $this->_sqlDB->real_escape_string($toCity);
                // Поиск в БД на совпадение по городам:
                $data = $this->_sqlDB->query("SELECT * FROM cities WHERE City='$fromCity' AND Base_City='$toCity' AND (Direction='BOTH' OR Direction='FROM')
                                            UNION SELECT * FROM cities WHERE Base_City='$fromCity' AND City='$toCity' AND (Direction='BOTH' OR Direction='TO')");
                if (false === $data) {
                    $res["errMsg"] = "Query is not correct";
                    $res["result"] = false;
                } else {
                    $part = "\{[a-z]+\:\[[0-9]+(?:-[0-9]+)\]\}";
                    $dataPattern = "/\[([0-9]+)\]\[([0-9]+(?:\.|\,|)[0-9]*)\]($part)($part)?($part)?($part)?/ui";
                    // Читаем данные в ассоциативные массив.
                    $row = $data->fetch_array();
                    // Проверяем результат чтения БД (таблица cities).
                    if (1 === $data->num_rows) {
                        if (1 === preg_match($dataPattern, $row["Data"], $dataMatches)) {
                            $res["result"] = true;
                            // Получаем номер зоны, коэф. и даты доставок.
                            $zone = $dataMatches[1];
                            // Если направление из опорного города в населенный пункт - коэффициент = 1:
                            if (($fromCity === $row["Base_City"]) && ($toCity === $row["City"])) {
                                $coeff = "1.0";
                            } 
                            // Если направление из населенного пункта в город - коэф. из данных:
                            else if (($toCity === $row["Base_City"]) && ($fromCity === $row["City"])) {
                                $coeff = $dataMatches[2];
                            } else {
                                $coeff = "0.0";
                            }
                            for ($i = 3; $i < count($dataMatches); ++$i) {
                                $datePattern = "/\{([a-z]+)\:\[([0-9]+(?:-[0-9]+))\]/ui";
                                if (1 === preg_match($datePattern, $dataMatches[$i], $dateMatches)) {
                                    $modeDates[$dateMatches[1]] = $dateMatches[2];
                                } else {
                                    $res["result"] = false;
                                    $res["errMsg"] = "[cities] Check your date-format for: $dataMatches[$i]";
                                    break;
                                }
                            }
                        } else {
                            $res["result"] = false;
                            $rowId = $row['ID'];
                            $res["errMsg"] = "[cities] Check 'Data' column for your string patterns id=$rowId";
                        }
                    } else if (0 === $data->num_rows) {
                        $res["result"] = false;
                        $res["forUserMsg"] = "Нет результатов. Выберите населенные пункты из выпадающих списков";
                    } else {
                        $res["result"] = false;
                        $res["forUserMsg"] = "Уточните населенные пункты. Выберите населенные пункты из выпадающих списков";
                    }
                }
            } else {
                $res["result"] = false;
                $res["forUserMsg"] = "Названия населенных пунктов не соответствуют формату. Выберите населенные пункты из выпадающих списков";
            }
            // Вторая часть:
            if ((true === $this->_result["connect"]) && (true === $res["result"])) {
                // Поиск в БД на совпадение по номеру зоны:
                $data = $this->_sqlDB->query("SELECT * FROM zones WHERE Zone='$zone'");
                if (false === $data) {
                    $res["errMsg"] = "Query is not correct";
                    $res["result"] = false;
                } else {
                    if ($data->num_rows > 0) {
                        $ratePattern = "/\{(\d+(\.\d+)?)\|(\d+(\.\d+)?)\|(\d+(\.\d+)?)\}/ui";
                        for ($i = 0; $i < $data->num_rows; ++$i) {
                            // Читаем данные в ассоциативные массив.
                            $row = $data->fetch_array();
                            // Проверяем данные:
                            if (1 === preg_match($ratePattern, $row["Data"], $rateMatches)) {
                                $modeRates[$i]["name"] = $row["Delivery_Type"];
                                $modeRates[$i][0] = $rateMatches[1];
                                $modeRates[$i][1] = $rateMatches[3];
                                $modeRates[$i][2] = $rateMatches[5];
                            } else {
                                $res["result"] = false;
                                $res["errMsg"] = "[zones] Check your data-format for: $rateMatches[0]";
                                break;
                            }
                        }
                    } else {
                        $res["result"] = false;
                        $res["errMsg"] = "[zones] Not found this zone-number: $zone";
                    }
                }
            }
          
            // Третья часть:
            if ((true === $this->_result["connect"]) && (true === $res["result"])) {
                $object = array();
                $modes = array();
                $object["coeff"] = $coeff;
                for ($i = 0; $i < count($modeRates); ++$i) {
                    $tmpName = $modeRates[$i]["name"];
                    $modes[$i]["mode"] = $tmpName;
                    $modes[$i][0] = $modeRates[$i][0];
                    $modes[$i][1] = $modeRates[$i][1];
                    $modes[$i][2] = $modeRates[$i][2];
                    if (isset($modeDates[$tmpName])) {
                        $modes[$i]["date"] = $modeDates[$tmpName];
                    }
                    else {
                        $object[$i]["date"] = "N/A";
                    }
                }
                $object["modes"] = $modes;
                $res["DATA"] = $object;
            }
            return $res;
        }
      
        function isSuccess() { return $this->_result["success"]; }
      
        function getConnectResult() { return $this->_result["connect"]; }
        
    }
    
    $DB = new calculatorDB();

    // Проверяем соединение с БД:
    if (true !== $DB->getConnectResult()) {
        $result["connect"] = false;
        echo json_encode($result);
    } else if (isset($_POST["city_list"]) && isset($_POST["toCityString"]) && !empty($_POST["baseCityString"])) {
        $result["connect"] = true;
        $tmpRes = $DB->getCityList($_POST["baseCityString"], $_POST["toCityString"]);
        if (true === $tmpRes["result"]) {
            $result["success"] = true;
            $result["cities"] = $tmpRes["cities"];
            $result["num_rows"] = $tmpRes["num_rows"];
        } else {
            $result["success"] = false;
            $result["error"] = $tmpRes["errMsg"];
        }
        echo json_encode($result);
    } else if (isset($_POST["base_city_list"]) && isset($_POST["baseCityString"])) {
        $result["connect"] = true;
        $tmpRes = $DB->getBaseCityList($_POST["baseCityString"]);
        if (true === $tmpRes["result"]) {
            $result["success"] = true;
            $result["cities"] = $tmpRes["cities"];
            $result["num_rows"] = $tmpRes["num_rows"];
        } else {
            $result["success"] = false;
            $result["message"] = $tmpRes["forUserMsg"];
            $result["error"] = $tmpRes["errMsg"];
        }
        echo json_encode($result);
    } else if (isset($_POST["calculate_data"]) && !empty($_POST["fromCity"]) && !empty($_POST["toCity"])) {
        $result["connect"] = true;
        $tmpRes = $DB->getData($_POST["fromCity"], $_POST["toCity"]);
        if (true === $tmpRes["result"]) {
            $result["success"] = true;
            $result["DATA"] = $tmpRes["DATA"];
        } else {
            $result["success"] = false;
            $result["message"] = $tmpRes["forUserMsg"];
            $result["error"] = $tmpRes["errMsg"];
        }
        echo json_encode($result);
    } else {
        $result["success"] = false;
        echo json_encode($result);
    }

?>