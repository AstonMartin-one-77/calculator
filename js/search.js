'use strict';
/* ПУНКТ ОТПРАВКИ: */
var baseCityList = new Array();
var isReady = false;

function requestBaseCityList(userString) {
    $.ajax({
        type: "post",
        url: "php/search.php",
        data: {
            "baseCityString": userString
        },
        response: "text",
        success: function(list) {
            /* Обновляем список городов. */
            setBaseCityList(list.cities);
            var cities = getBaseCityList($("input#baseCity").val());
            $("ul#baseCityResult").html(cities).fadeIn();
        },
        error: function(xhr, status, error) {
            $("span#alert-message").text(error.message);
            $("div#connect-db-alert").show();
            
        },
        dataType: "json"
    });
}

function setBaseCityList(list) {
    baseCityList = list;
    isReady = true;
}

function getBaseCityList(userString) {
    var result = ["Нет результатов"];
    if (isReady) {
        /* Формируем массив на основе совпадения с строкой пользователя. */
        var baseList = $.grep(baseCityList, function(element, index) {
            if (element.toLowerCase().indexOf(userString.toLowerCase()) === 0) {
                return true;
            }
            else {
                return false;
            }
        });
        if (baseList.length > 0) {
            result = baseList;
        }
    }
    /** Wrapper for list to html-code. */
    result.forEach(function(item, index, array) {
        array[index] = "\n<li><span class='city' id='baseCity" + index + "'>" + item + "</span></li>";
    });
    return result;
}
/* ПУНКТ ДОСТАВКИ: */
var cityList = new Array();
var listIsReady = false;

function requestCityList(baseCity, userString) {
    $.ajax({
        type: "post",
        url: "php/search.php",
        data: {
            "baseCityString": baseCity,
            "toCityString": userString
        },
        response: "text",
        success: function(list) {
            if (("correct" === list.request) && (true === list.success)) {
                /* Обновляем список городов. */
                setCityList(list.cities);
                var cities = getCityList($("input#city").val());
                $("ul#cityResult").html(cities).fadeIn();
            }
        },
        error: function(xhr, status, error) {
            $("span#alert-message").text(error.message);
            $("div#connect-db-alert").show();
            
        },
        dataType: "json"
    });
}

function setCityList(list) {
    cityList = list;
    listIsReady = true;
}

function getCityList(userString) {
    var result = ["Нет результатов"];
    if (listIsReady) {
        /* Формируем массив на основе совпадения с строкой пользователя. */
        var list = $.grep(cityList, function(element, index) {
            if (element.toLowerCase().indexOf(userString.toLowerCase()) === 0) {
                return true;
            }
            else {
                return false;
            }
        });
        if (list.length > 0) {
            result = list;
        }
    }
    /** Wrapper for list to html-code. */
    result.forEach(function(item, index, array) {
        array[index] = "\n<li><span class='city' id='city" + index + "'>" + item + "</span></li>";
    });
    return result;
}


$(function() {
    // ПУНКТ ОТПРАВКИ:
    // 1. Отправка запроса:
    $("input#baseCity").bind("change keyup input click", function() {
        if (($(this).val().length % 2) === 0) {
            var cities = getBaseCityList($("input#baseCity").val());
            $("ul#baseCityResult").html(cities).fadeIn();
            /* Если мало городов в списке - обновляем: */
            if (cities.length < 5) {
                // Запрос данных от сервера.
                requestBaseCityList($(this).val());
            }
        }
        else {
            requestBaseCityList($(this).val());
        }
    });
    // 2. При нажатии кнопки tab|enter - автозаполнение
    $("input#baseCity").bind("keypress keydown", function(key) {
        if ((13 === key.which) || (9 === key.which)) {
            /** Первый элемент списка. */
            var firstElem = $("ul#baseCityResult > li > span#baseCity0");
            if ("Нет результатов" !== firstElem.text())
            {
                $(this).val(firstElem.text());
                $("ul#baseCityResult").fadeOut();
                $("input#city").focus();
            }
            else {
                $("input#baseCity").focus();
                
            }
        }
        /** Выключаем остальные действия TAB. */
        if (9 === key.which) { key.preventDefault(); };
    });
    // 3. При выборе результата поиска - спрятать список и занести результат в поле ввода:
    $("ul#baseCityResult").on("click", "li", function() {
        $("input#baseCity").val($(this).text());
        $("ul#baseCityResult").fadeOut();
    });
    // 4. Спрятать список, если клик вне поля:
    $(document).mouseup(function(event) {
        var input = $("input#baseCity");
        var list = $("ul#baseCityResult");

        if (!input.is(event.target) && !list.is(event.target) && 
            (input.has(event.target).length === 0) && 
            (input.has(event.target).length === 0))
        {
            $("ul#baseCityResult").fadeOut();
        }
    });
  
    // ПУНКТ ДОСТАВКИ:
    // 1. Отправка запроса:
    $("input#city").bind("change keyup input click", function() {
        if (($(this).val().length % 2) === 0) {
            var cities = getCityList($(this).val());
            $("ul#cityResult").html(cities).fadeIn();
            /* Если мало городов в списке - обновляем: */
            if (cities.length < 5) {
                // Запрос данных от сервера.
                requestCityList($("input#baseCity").val(), $(this).val());
            }
        }
        else {
            requestCityList($("input#baseCity").val(), $(this).val());
        }
    });
    // 2. При нажатии кнопки tab|enter - автозаполнение
    $("input#city").bind("keypress", function(key) {
        if ((13 === key.which) || (9 === key.which)) {
            
        }
    });
    // 3. При выборе результата поиска - спрятать список и занести результат в поле ввода:
    $("ul#cityResult").on("click", "li", function() {
        $("input#city").val($(this).text());
        $("ul#cityResult").fadeOut();
    });
    // 4. Спрятать список, если клик вне поля:
    $(document).mouseup(function(event) {
        var input = $("input#city");
        var list = $("ul#cityResult");

        if (!input.is(event.target) && !list.is(event.target) && 
            (input.has(event.target).length === 0) && 
            (input.has(event.target).length === 0))
        {
            $("ul#cityResult").fadeOut();
        }
    });
});