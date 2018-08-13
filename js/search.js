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
            var cities = getBaseCityList($("div.calculator input#baseCity").val());
            $("div.calculator ul#baseCityResult").html(cities).fadeIn();
        },
        error: function(xhr, status, error) {
            $("div.calculator span#alert-message").text(error.message);
            $("div.calculator div#connect-db-alert").show();
            
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
                var cities = getCityList($("div.calculator input#city").val());
                $("div.calculator ul#cityResult").html(cities).fadeIn();
            }
        },
        error: function(xhr, status, error) {
            $("div.calculator span#alert-message").text(error.message);
            $("div.calculator div#connect-db-alert").show();
            
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
    $("div.calculator input#baseCity").bind("change keyup input click", function() {
        if (($(this).val().length % 2) === 0) {
            var cities = getBaseCityList($("div.calculator input#baseCity").val());
            $("div.calculator ul#baseCityResult").html(cities).fadeIn();
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
    $("div.calculator input#baseCity").bind("keydown", function(key) {
        if ((13 === key.which) || (9 === key.which)) {
            /** Первый элемент списка. */
            var firstElem = $("div.calculator ul#baseCityResult > li > span#baseCity0");
            if ("Нет результатов" !== firstElem.text())
            {
                $(this).val(firstElem.text());
                $("div.calculator ul#baseCityResult").fadeOut();
                $("div.calculator input#city").focus();
            }
            else {
                $("div.calculator input#baseCity").focus();
                
            }
            key.preventDefault();
        }
    });
    // 3. При выборе результата поиска - спрятать список и занести результат в поле ввода:
    $("div.calculator ul#baseCityResult").on("click", "li", function() {
        var text = $(this).children("span").text();
        if ("Нет результатов" !== text) {
            $("div.calculator input#baseCity").val(text);
            $("div.calculator ul#baseCityResult").fadeOut();
        }
      else {
          $("div.calculator input#baseCity").focus();
      }
    });
    // 4. Спрятать список, если клик вне поля:
    $(document).mouseup(function(event) {
        var input = $("div.calculator input#baseCity");
        var list = $("div.calculator ul#baseCityResult");

        if (!input.is(event.target) && !list.is(event.target) && 
            (input.has(event.target).length === 0) && 
            (input.has(event.target).length === 0))
        {
            $("div.calculator ul#baseCityResult").fadeOut();
        }
    });
  
    // ПУНКТ ДОСТАВКИ:
    // 1. Отправка запроса:
    $("div.calculator input#city").bind("change keyup input click", function() {
        if (($(this).val().length % 2) === 0) {
            var cities = getCityList($(this).val());
            $("div.calculator ul#cityResult").html(cities).fadeIn();
            /* Если мало городов в списке - обновляем: */
            if (cities.length < 5) {
                // Запрос данных от сервера.
                requestCityList($("div.calculator input#baseCity").val(), $(this).val());
            }
        }
        else {
            requestCityList($("div.calculator input#baseCity").val(), $(this).val());
        }
    });
    // 2. При нажатии кнопки tab|enter - автозаполнение
    $("div.calculator input#city").bind("keydown", function(key) {
        if ((13 === key.which) || (9 === key.which)) {
            /** Первый элемент списка. */
            var firstElem = $("div.calculator ul#cityResult > li > span#city0");
            if ("Нет результатов" !== firstElem.text())
            {
                $(this).val(firstElem.text());
                $("div.calculator ul#cityResult").fadeOut();
                $("div.calculator button#btnAddDespatch").focus();
            }
            else {
                $("div.calculator input#city").focus();
                
            }
            key.preventDefault();
        }
    });
    // 3. При выборе результата поиска - спрятать список и занести результат в поле ввода:
    $("div.calculator ul#cityResult").on("click", "li", function() {
        var text = $(this).children("span").text();
        if ("Нет результатов" !== text) {
            $("div.calculator input#city").val(text);
            $("div.calculator ul#cityResult").fadeOut();
        }
        else {
            $("div.calculator input#city").focus();
        }
    });
    // 4. Спрятать список, если клик вне поля:
    $(document).mouseup(function(event) {
        var input = $("div.calculator input#city");
        var list = $("div.calculator ul#cityResult");

        if (!input.is(event.target) && !list.is(event.target) && 
            (input.has(event.target).length === 0) && 
            (input.has(event.target).length === 0))
        {
            $("div.calculator ul#cityResult").fadeOut();
        }
    });
  
    // Популярные города:
    $("div.calculator div.from-city > a.popularCities").bind("click", function() {
        var cityName = $(this).text();
        $.ajax({
            type: "post",
            url: "php/search.php",
            data: {
                "baseCityString": cityName
            },
            response: "text",
            success: function(list) {
                if (("correct" === list.request) && (true === list.success)) {
                    if (1 === list.cities.length) {
                        var fullName = list.cities[0];
                        $("div.calculator input#baseCity").val(fullName);
                    }
                }
            },
            error: function(xhr, status, error) {
                $("div.calculator span#alert-message").text(error.message);
                $("div.calculator div#connect-db-alert").show();

            },
            dataType: "json"
        });
    });
    $("div.calculator div.to-city > a.popularCities").bind("click", function() {
        var cityName = $(this).text();
        $.ajax({
            type: "post",
            url: "php/search.php",
            data: {
                "baseCityString": cityName
            },
            response: "text",
            success: function(list) {
                if (("correct" === list.request) && (true === list.success)) {
                    if (1 === list.cities.length) {
                        var fullName = list.cities[0];
                        $("div.calculator input#city").val(fullName);
                    }
                }
            },
            error: function(xhr, status, error) {
                $("div.calculator span#alert-message").text(error.message);
                $("div.calculator div#connect-db-alert").show();

            },
            dataType: "json"
        });
    });
});