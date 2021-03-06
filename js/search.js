'use strict';
/* ПУНКТ ОТПРАВКИ: */
var baseCityList = new Array();
var isReady = false;

function requestBaseCityList(userString) {
    $.ajax({
        type:       "post",
        url:        "php/search.php",
        dataType:   "json",
        data: {
                    "base_city_list": 1,
                    "baseCityString": userString
        },
        response:   "json",
        success:    function(response, status, jqXHR) {
            if (true === response.success) {
                /* Обновляем список городов. */
                setBaseCityList(response.cities);
                var cities = getBaseCityList($("div.calculator input#baseCity").val());
                $("div.calculator ul#baseCityResult").html(cities).fadeIn();
            } else {
                if ((undefined !== response.error) && (null !== response.error)) {
                    $("div.calculator div#search-db-alert span#search-alert-message")
                        .html("Ошибка!<br>Обратитесь в службу поддержки.<br>" + 
                              "Сообщение об ошибке:<br>" + response.error);
                    $("div.calculator div#search-db-alert").prop("hidden", false);
                } else if ((undefined !== response.message) && (null !== response.message)) {
                    alert(response.message);
                }
            }
        },
        error:      function(xhr, status, error) {
            $("div.calculator div#search-db-alert span#search-alert-message")
                .html("Ошибка!<br>Обратитесь в службу поддержки.<br>Код ошибки: " + 
                      error.message + "<br>" + xhr.responseText);
            $("div.calculator div#search-db-alert").prop("hidden", false);
        }
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
        type:       "post",
        url:        "php/search.php",
        dataType:   "json",
        data: {
                    "city_list": 1,
                    "baseCityString": baseCity,
                    "toCityString": userString
        },
        response:   "json",
        success:    function(response, status, jqXHR) {
            if (true === response.success) {
                /* Обновляем список городов. */
                setCityList(response.cities);
                var cities = getCityList($("div.calculator input#city").val());
                $("div.calculator ul#cityResult").html(cities).fadeIn();
            } else {
                if ((undefined !== response.error) && (null !== response.error)) {
                    $("div.calculator div#search-db-alert span#search-alert-message")
                        .html("Ошибка!<br>Обратитесь в службу поддержки.<br>" + 
                              "Сообщение об ошибке:<br>" + response.error);
                    $("div.calculator div#search-db-alert").prop("hidden", false);
                } else if ((undefined !== response.message) && (null !== response.message)) {
                    alert(response.message);
                }
            }
        },
        error:      function(xhr, status, error) {
            $("div.calculator div#search-db-alert span#search-alert-message")
                .html("Ошибка!<br>Обратитесь в службу поддержки.<br>Код ошибки: " + 
                      error.message + "<br>" + xhr.responseText);
            $("div.calculator div#search-db-alert").prop("hidden", false);
        }
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
            type:       "post",
            url:        "php/search.php",
            dataType:   "json",
            data: {
                        "base_city_list": 1,
                        "baseCityString": cityName
            },
            response:   "json",
            success:    function(response, status, jqXHR) {
                if (true === response.success) {
                    if (1 === response.cities.length) {
                        var fullName = response.cities[0];
                        $("div.calculator input#baseCity").val(fullName);
                    }
                } else {
                    if ((undefined !== response.error) && (null !== response.error)) {
                        $("div.calculator div#search-db-alert span#search-alert-message")
                            .html("Ошибка!<br>Обратитесь в службу поддержки.<br>" + 
                                  "Сообщение об ошибке:<br>" + response.error);
                        $("div.calculator div#search-db-alert").prop("hidden", false);
                    } else if ((undefined !== response.message) && (null !== response.message)) {
                        alert(response.message);
                    }
                }
            },
            error:      function(xhr, status, error) {
                $("div.calculator div#search-db-alert span#search-alert-message")
                .html("Ошибка!<br>Обратитесь в службу поддержки.<br>Код ошибки: " + 
                      error.message + "<br>" + xhr.responseText);
                $("div.calculator div#search-db-alert").prop("hidden", false);
            }
        });
    });
    $("div.calculator div.to-city > a.popularCities").bind("click", function() {
        var cityName = $(this).text();
        $.ajax({
            type:       "post",
            url:        "php/search.php",
            dataType:   "json",
            data: {
                        "base_city_list": 1,
                        "baseCityString": cityName
            },
            response:   "json",
            success:    function(response, status, jqXHR) {
                if (true === response.success) {
                    if (1 === response.cities.length) {
                        var fullName = response.cities[0];
                        $("div.calculator input#city").val(fullName);
                    }
                } else {
                    if ((undefined !== response.error) && (null !== response.error)) {
                        $("div.calculator div#search-db-alert span#search-alert-message")
                            .html("Ошибка!<br>Обратитесь в службу поддержки.<br>" + 
                                  "Сообщение об ошибке:<br>" + response.error);
                        $("div.calculator div#search-db-alert").prop("hidden", false);
                    } else if ((undefined !== response.message) && (null !== response.message)) {
                        alert(response.message);
                    }
                }
            },
            error:      function(xhr, status, error) {
                $("div.calculator div#search-db-alert span#search-alert-message")
                .html("Ошибка!<br>Обратитесь в службу поддержки.<br>Код ошибки: " + 
                      error.message + "<br>" + xhr.responseText);
                $("div.calculator div#search-db-alert").prop("hidden", false);
            }
        });
    });
});

// ОБРАБОТЧИК РАСЧЕТА КАЛЬКУЛЯТОРА:
function calculate() {
    // Скрываем таблицу результата.
    $("div.calculator div#calculate-result").toggle(false);
    // Удаляем предыдущие строки.
    $("div.calculator tbody.body-results > tr.row").each(function(index, element) {
        $(this).remove();
    });
    $("div.calculator tbody.body-results > tr.row-without-contract").each(function(index, element) {
        $(this).remove();
    });
    var fromCity = $("div.calculator input#baseCity").val();
    var toCity = $("div.calculator input#city").val();
    $.ajax({
        type:       "post",
        url:        "php/search.php",
        dataType:   "json",
        data: {
                    "calculate_data": 1,
                    "fromCity": fromCity,
                    "toCity": toCity
        },
    response:       "json",
        success:    function(response, status, jqXHR) {
            if (true === response.success) {
                var despatchList = $("div.calculator div.despatchList").children("div.despatch");
                var fullWeight = 0;
                if (despatchList.length > 0) {
                    despatchList.each(function(index, element){
                        var weight = getFloat($(this).find("input#weight").val());
                        var amount = getFloat($(this).find("input#amount").val());
                        var volumeWeight = 0;
                        switch($(this).find("img#despatch-icon").attr("alt")) {
                            case "envelope":
                                if (weight > 0.5) {
                                    var len = getFloat($(this).find("input#env-length").val());
                                    var width = getFloat($(this).find("input#env-width").val());
                                    var height = getFloat($(this).find("input#env-height").val());
                                    volumeWeight = (len * width * height) / 5000;
                                }
                                break;
                            case "box":
                                var len = getFloat($(this).find("input#box-length").val());
                                var width = getFloat($(this).find("input#box-width").val());
                                var height = getFloat($(this).find("input#box-height").val());
                                volumeWeight = (len * width * height) / 5000;
                                break;
                            case "roll":
                                var len = getFloat($(this).find("input#roll-length").val());
                                var diameter = getFloat($(this).find("input#roll-diameter").val());
                                volumeWeight = (len * diameter * Math.PI) / 5000;
                                break;
                        }
                        // Вес для данной позиции:
                        var tmpWeight = (weight >= volumeWeight) ? weight : volumeWeight;
                        // Добавляем в общий вес в соответствии с кол-вом позиций:
                        fullWeight += tmpWeight * amount;
                    });
                    // Округляем вес:
                    fullWeight = (fullWeight <= 0.5) ? 0.5 : Math.ceil(fullWeight.toFixed(1));
                    for (var i = 0, modes = response.DATA.modes, coeff = getFloat(response.DATA.coeff); i < modes.length; ++i) {
                        var fullCost = 0;
                        var baseCost_0_5 = Number.parseInt(modes[i][0]);
                        var baseCost_1 = Number.parseInt(modes[i][1]);
                        var additionCost = Number.parseInt(modes[i][2]);
                        if (fullWeight <= 0.5) {
                            fullCost = baseCost_0_5 * coeff;
                        } else if (fullWeight <= 1) {
                            fullCost = baseCost_1 * coeff;
                        } else {
                            fullCost = (baseCost_1 + (fullWeight - 1) * additionCost) * coeff;
                        }
                        var newRow = $("div.calculator tbody.body-results > tr.row-parent").clone(true);
                        newRow.toggleClass("row-parent row");
                        newRow.find("th.mode").text(getRusMode(modes[i]["mode"]));
                        newRow.find("th.date").text(modes[i]["date"]);
                        newRow.find("th.cost").text(Math.ceil(fullCost));
                        newRow.appendTo("div.calculator tbody.body-results");
                        newRow.toggle(true);
                    }
                    // Отображаем таблицу результата.
                    $("div.calculator div#calculate-result").toggle(true);
                    // Специально для работы в iframe:
                    $("html").click();
                }
            } else {
                if ((undefined !== response.message) && (null !== response.message)) {
                    alert(response.message);
                } else if ((undefined !== response.error) && (null !== response.error)) {
                    $("div.calculator div#calculate-db-alert span#calculate-alert-message")
                        .html("Ошибка!<br>Обратитесь в службу поддержки.<br>" + 
                              "Сообщение об ошибке:<br>" + response.error);
                    $("div.calculator div#calculate-db-alert").prop("hidden", false);
                }
            }
        },
        error:      function(xhr, status, error) {
            $("div.calculator div#calculate-db-alert span#calculate-alert-message")
                .html("Ошибка!<br>Обратитесь в службу поддержки.<br>Код ошибки: " + 
                      error.message + "<br>" + xhr.responseText);
            $("div.calculator div#calculate-db-alert").prop("hidden", false);
        }
    });
};

function getFloat(floatStr) {
    return Number.parseFloat(floatStr.replace(/,/, '.'));
}

function getRusMode(enMode) {
    switch(enMode) {
        case "economy":
            return "Эконом";
        case "standart":
            return "Стандарт";
        case "express":
            return "Экспресс";
        case "superexpress":
            return "Суперэкспресс";
        default:
            return "Не определен";
    };
}

