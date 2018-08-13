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
                    $this->result["getBaseCityList"] = "Error";
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
                    $this->result["getCityList"] = "Error";
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
    else {
        $result["success"] = false;
        $result["request"] = "unknown";
        $result["isset(toCityString)"] = isset($_POST["toCityString"]);
        $result["isset(baseCityString)"] = isset($_POST["baseCityString"]);
        $result["POST"] = $_POST;
        echo json_encode($result);
    }

?>