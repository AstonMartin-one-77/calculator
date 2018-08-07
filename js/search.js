'use strict';

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
            cities.forEach(function(item, index, array) {
                array[index] = "\n<li><span class='city'>" + item + "</span></li>";
            });
            $("ul#baseCityResult").html(cities).fadeIn();
        },
        error: function(xhr, status, error) {
            status.text();
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
    return result;
}

$(function() {
    // ПУНКТ ОТПРАВЛЕНИЯ:
    // 1. Отправка запроса:
    $("input#baseCity").bind("change keyup input click", function() {
        if (($(this).val().length % 2) === 0) {
            var cities = getBaseCityList($("input#baseCity").val());
            cities.forEach(function(item, index, array) {
                array[index] = "\n<li><span class='city'>" + item + "</span></li>";
            });
            $("ul#baseCityResult").html(cities).fadeIn();
            /* Если мало городов в списке - обновляем: */
            if (cities.length < 10) {
                // Запрос данных от сервера.
                requestBaseCityList($(this).val());
            }
        }
        else {
            requestBaseCityList($(this).val());
        }
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
});