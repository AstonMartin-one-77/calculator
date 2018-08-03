<?php 

    $cityWrap = array("<span class='city'></span>");
    $areaWrap = "<span class='cityArea'></span>";
    $cities = array("Санкт-Петербург", "Москва", "Красноярск");
    $aries = array("Ленинградская обл.", "Московская обл.", "Красноярский край");
    
    if (!empty($_POST["baseCityStr"])) {
        $userString = $_POST["baseCityStr"];
        
        for ($count = 0; $count < count($cities); ++$count) {
            echo "\n<li><span class='city'>$cities[$count]</span> <span class='cityArea'>($aries[$count])</span></li>";
        }
    }

?>