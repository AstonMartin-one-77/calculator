<?php 

    class calculatorDB {

        const DB_HOST = "calculator.ru";
        const DB_NAME = "calculator";
        const DB_USER = "calculatorUser";
        const DB_PASSWORD = "calculator.ru";
        private $sqlDB = null;
        private $result = null;
        
        function __construct() {
            $this->sqlDB = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASSWORD, self::DB_NAME);
            if (!$this->sqlDB->query("SET NAMES 'utf8'")) {
                $this->result["success"] = false;
                $this->result["connect"] = "Could not connect to DB";
            }
            else {
                $this->result["success"] = true;
                $this->result["connect"] = "Success connect to DB";
            }
        }
      
        function getBaseCityList($userString) {
            $pattern = "/(([а-я]*)(-[а-я]+)?(-[а-я]+)?)( \(([а-я]*) ([а-я]*)(\.\)|\)|\.|))?/ui";
            if ((true === $this->result["success"]) && (true === is_string($userString)) && 
                /** Проверяем на паттерн, предварительно удалив все лишние символы из запроса. */
                (1 === preg_match($pattern, trim(strip_tags($userString)), $matches))) {
                /** Если найдено совпадение в запросе по паттерну, копируем результат. */
                $userString = $matches[0];
                //$this->result["userString"] = $userString;
                /** Поиск в БД на совпадение городам. */
                $data = $this->sqlDB->query("SELECT DISTINCT Base_City AS all_city FROM cities WHERE Base_City LIKE '$userString%' UNION SELECT DISTINCT City AS all_city FROM cities WHERE City LIKE '$userString%'");
                /** Проверяем результат. */
                if (false === $data) {
                    $this->result["success"] = false;
                    $this->result["getBaseCityList"] = "error";
                    $this->result["error"] = $this->sqlDB->error;
                }
                else {
                    $this->result["getBaseCityList"] = "success";
                    $this->result["num_rows"] = $data->num_rows;
                    $baseCities = array();
                    for ($i = 0; $i < $data->num_rows; ++$i) {
                        $tmp = $data->fetch_row();
                        $baseCities[$i] = $tmp[0];
                    }
                    $this->result["cities"] = $baseCities;
                }
            }
        }
      
        function getCityList($baseCity, $userString) {
            $pattern = "/(([а-я]*)(-[а-я]+)?(-[а-я]+)?)( \(([а-я]*) ([а-я]*)(\.\)|\)|\.|))?/ui";
            
            if ((true === $this->result["success"]) && 
                (true === is_string($baseCity)) && 
                (true === is_string($userString)) && 
                /** Проверяем на паттерн, предварительно удалив все лишние символы из запроса. */
                (1 === preg_match($pattern, trim(strip_tags($baseCity)), $baseMatches)) && 
                (1 === preg_match($pattern, trim(strip_tags($userString)), $matches))) {
                /** Если найдено совпадение в запросе по паттерну, копируем результат. */
                $baseCity = $baseMatches[0];
                $userString = $matches[0];
                /** Поиск в БД на совпадение по городам. */
                $data = $this->sqlDB->query("SELECT DISTINCT Base_City AS all_city FROM cities WHERE City='$baseCity' AND Base_City LIKE '$userString%' 
                                            UNION SELECT DISTINCT City AS all_city FROM cities WHERE Base_City='$baseCity' AND City LIKE '$userString%'");
                /** Проверяем результат. */
                if (false === $data) {
                    $this->result["success"] = false;
                    $this->result["getCityList"] = "error";
                    $this->result["error"] = $this->sqlDB->error;
                }
                else {
                    $this->result["getCityList"] = "success";
                    $this->result["num_rows"] = $data->num_rows;
                    $baseCities = array();
                    for ($i = 0; $i < $data->num_rows; ++$i) {
                        $tmp = $data->fetch_row();
                        $baseCities[$i] = $tmp[0];
                    }
                    $this->result["cities"] = $baseCities;
                }
            }
        }
      
        function getData($fromCity, $toCity) {
            $pattern = "/(([а-я]*)(-[а-я]+)?(-[а-я]+)?)( \(([а-я]*) ([а-я]*)(\.\)|\)|\.|))?/ui";
            // Формируемые данные (инициализируем стандартными данными):
            $zone = 0;
            $coeff = 1;
            $modeDates = array();
            $modeRates = array();
            // Первая часть:
            if ((true === $this->result["success"]) && 
                (true === is_string($fromCity)) && 
                (true === is_string($toCity)) && 
                /** Проверяем на паттерн, предварительно удалив все лишние символы из запроса. */
                (1 === preg_match($pattern, trim(strip_tags($fromCity)), $fromCityMatches)) && 
                (1 === preg_match($pattern, trim(strip_tags($toCity)), $toCityMatches))) {
                /** Если найдено совпадение в запросе по паттерну, копируем результат. */
                $fromCity = $fromCityMatches[0];
                $toCity = $toCityMatches[0];
                /** Поиск в БД на совпадение по городам. */
                $data = $this->sqlDB->query("SELECT * FROM cities WHERE City='$fromCity' AND Base_City='$toCity' AND (Direction='BOTH' OR Direction='FROM')
                                            UNION SELECT * FROM cities WHERE Base_City='$fromCity' AND City='$toCity' AND (Direction='BOTH' OR Direction='TO')");
                if (false === $data) {
                    $this->result["success"] = false;
                    $this->result["getData"] = "error";
                    $this->result["error"] = $this->sqlDB->error;
                }
                else {
                    $part = "\{[a-z]+\:\[[0-9]+(?:-[0-9]+)\]\}";
                    $dataPattern = "/\[([0-9]+)\]\[([0-9]+(?:\.|\,|)[0-9]*)\]($part)($part)?($part)?($part)?/ui";
                    // Читаем данные в ассоциативные массив.
                    $row = $data->fetch_array();
                    // Проверяем результат чтения БД (таблица cities).
                    if (1 === $data->num_rows) {
                        if (1 === preg_match($dataPattern, $row["Data"], $dataMatches)) {
                            // Получаем номер зоны, коэф. и даты доставок.
                            $zone = $dataMatches[1];
                            $coeff = $dataMatches[2];
                            for ($i = 3; $i < count($dataMatches); ++$i) {
                                $datePattern = "/\{([a-z]+)\:\[([0-9]+(?:-[0-9]+))\]/ui";
                                if (1 === preg_match($datePattern, $dataMatches[$i], $dateMatches)) {
                                    $modeDates[$dateMatches[1]] = $dateMatches[2];
                                }
                                else {
                                    $this->result["success"] = false;
                                    $this->result["getData"] = "error";
                                    $this->result["error"] = "[cities] Check your date-format for: $dataMatches[$i]";
                                    break;
                                }
                            }
                        }
                        else {
                            $this->result["success"] = false;
                            $this->result["getData"] = "error";
                            $rowId = $row['ID'];
                            $this->result["error"] = "[cities] Check 'Data' column for your string patterns id=$rowId";
                        }
                    }
                    else {
                        $this->result["success"] = false;
                        $this->result["getData"] = "error";
                        $this->result["error"] = "Set of rows for this names (you should check DB)";
                    }
                }
            }
            else {
                $this->result["success"] = false;
                $this->result["getData"] = "error";
                $this->result["error"] = "Check your string pattern for city names";
            }
            // Вторая часть:
            if (true === $this->result["success"]) {
                /** Поиск в БД на совпадение по номеру зоны. */
                $data = $this->sqlDB->query("SELECT * FROM zones WHERE Zone='$zone'");
                if (false === $data) {
                    $this->result["success"] = false;
                    $this->result["getData"] = "error";
                    $this->result["error"] = $this->sqlDB->error;
                }
                else {
                    if ($data->num_rows > 0) {
                        $ratePattern = "/\{([0-9]+)\|([0-9]+)\|([0-9]+)\}/ui";
                        for ($i = 0; $i < $data->num_rows; ++$i) {
                            // Читаем данные в ассоциативные массив.
                            $row = $data->fetch_array();
                            // Проверяем данные:
                            if (1 === preg_match($ratePattern, $row["Data"], $rateMatches)) {
                                $modeRates[$i]["name"] = $row["Delivery_Type"];
                                $modeRates[$i][0] = $rateMatches[1];
                                $modeRates[$i][1] = $rateMatches[2];
                                $modeRates[$i][2] = $rateMatches[3];
                            }
                            else {
                                $this->result["success"] = false;
                                $this->result["getData"] = "error";
                                $this->result["error"] = "[zones] Check your data-format for: $rateMatches[0]";
                                break;
                            }
                        }
                    }
                    else {
                        $this->result["success"] = false;
                        $this->result["getData"] = "error";
                        $this->result["error"] = "[zones] Not found this zone-number: $zone";
                    }
                }
            }
          
            // Третья часть:
            if (true === $this->result["success"]) {
                $this->result["getData"] = "success";
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
                $this->result["DATA"] = $object;
            }
        }
      
        function isSuccess() { return $this->result["success"]; }
      
        function getResult() { return $this->result; }
        
    }
    
    $DB = new calculatorDB();

    if (isset($_POST["toCityString"]) && !empty($_POST["baseCityString"])) {
        $DB->getCityList($_POST["baseCityString"], $_POST["toCityString"]);
        $result = $DB->getResult();
        $result["request"] = "correct";
        echo json_encode($result);
    }
    else if (isset($_POST["baseCityString"])) {
        $DB->getBaseCityList($_POST["baseCityString"]);
        $result = $DB->getResult();
        $result["request"] = "correct";
        echo json_encode($result);
    }
    else if (!empty($_POST["fromCity"]) && !empty($_POST["toCity"])) {
        $DB->getData($_POST["fromCity"], $_POST["toCity"]);
        $result = $DB->getResult();
        $result["request"] = "correct";
        echo json_encode($result);
    }
    else {
        $result["success"] = false;
        $result["request"] = "unknown";
        $result["isset(toCityString)"] = isset($_POST["toCityString"]);
        $result["isset(baseCityString)"] = isset($_POST["baseCityString"]);
        $result["POST"] = $_POST;
        echo json_encode($result);
    }

?>