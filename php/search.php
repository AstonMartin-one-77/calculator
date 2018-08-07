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
            $pattern = "/([А-Яа-я]+[\-[А-Яа-я]+]?|)/";
            if ((true === $this->result["success"]) && (true === is_string($userString)) && 
                /** Проверяем на паттерн, предварительно удалив все лишние символы из запроса. */
                (1 === preg_match($pattern, $this->sqlDB->real_escape_string(trim(strip_tags($userString))), $matches))) {
                /** Если найдено совпадение в запросе по паттерну, копируем результат. */
                $userString = $matches[0];
                /** Поиск в БД на совпадение по опорным городам. */
                $data = $this->sqlDB->query("SELECT DISTINCT Base_City AS all_city FROM cities WHERE Base_City LIKE '$userString%' UNION SELECT DISTINCT City AS all_city FROM cities WHERE City LIKE '$userString%'");
                $this->result["error"] = $this->sqlDB->error;
                /** Проверяем результат. */
                if (false === $data) {
                    $this->result["success"] = false;
                    $this->result["getBaseCityList"] = "Error";
                }
                else {
                    $this->result["getBaseCityList"] = "success";
                    $baseCities = array($data->num_rows);
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
    $DB->getBaseCityList($_POST["baseCityString"]);
    
    echo json_encode($DB->getResult());
    
    /*if (isset($_POST["baseCityString"])) {
        $result = array();
        for ($i = 0; $i < count($cities); ++$i) {
            array_push($result, "$cities[$i] <span class='cityArea'>$aries[$i]</span>");
        }
      
        echo json_encode($result);
    }*/

?>