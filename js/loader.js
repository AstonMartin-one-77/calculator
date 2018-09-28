'use strict';

function loader() {
    // Переменная с данными формы:
    var form_data = new FormData();
    var userLogin = $("div.calculator input#userLogin").val();
    var userPassword = $("div.calculator input#userPassword").val();
    var dbHost = $("div.calculator input#dbHost").val();
    var dbName = $("div.calculator input#dbName").val();
    var dbUser = $("div.calculator input#dbUser").val();
    var dbPassword = $("div.calculator input#dbPassword").val();
    if (("" === userLogin) || ("" === userPassword) || ("" === dbHost) || 
        ("" === dbName) || ("" === dbUser) || ("" === dbPassword)) {
        alert("Все поля должны быть заполнены"); 
        return;
    }
    // Загружаем данные для доступа в переменную:
    form_data.append("userLogin", userLogin);
    form_data.append("userPassword", userPassword);
    form_data.append("dbHost", dbHost);
    form_data.append("dbName", dbName);
    form_data.append("dbUser", dbUser);
    form_data.append("dbPassword", dbPassword);
    // Загружаем все файлы в переменную:
    var files = $("div.calculator input#btnBrowse").prop("files");
    // Нет файлов - дальнейшей загрузки не происходит:
    if (0 === files.length) {
        alert("Должно быть выбрано не менее 1 файла (.xlsx)"); 
        return;
    }
    // Загружаем файлы в переменную:
    $.each(files, function(key, value) {
        // Требуются только .xlsx файлы:
        if (value.type !== "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
            alert("Для загрузки доступны только файлы MS Excel 2007+ (.xlsx)");
            return;
        }
        form_data.append(key, value);
    });
    form_data.append("excelCities_loader", 1);
    $.ajax({
        type:           "post",
        url:            "php/loader.php",
        dataType:       "json",
        data:           form_data,
        cache:          false,
        processData:    false,
        contentType:    false,
        response:       "json",
        success:        function(response, status, jqXHR) {
            if (true === response.success) {
                $("div.calculator div#load-db-alert").prop("hidden", true);
                var htmlInfoMsg = "Обновление прошло успешно!<br>";
                for (var i = 0; i < response.filesInfo.length; ++i) {
                    htmlInfoMsg += "<br>Имя файла: " + response.filesInfo[i].fileName + "<br>";
                    htmlInfoMsg += "Кол-во строк, записанных в БД: " + response.filesInfo[i].numEntries + "<br>";
                    if (null !== response.filesInfo[i].group_1) {
                        htmlInfoMsg += "Строки, которые были изменены при удалении всех записей для указанного опорного города:<br>";
                        for (var k = 0; k < response.filesInfo[i].group_1.length; ++k) {
                            htmlInfoMsg += "[" + k + "]: " + response.filesInfo[i].group_1[k];
                        }
                    }
                    if (null !== response.filesInfo[i].group_2) {
                        htmlInfoMsg += "Строки, которые были изменены после добавления всех записей для указанного опорного города:<br>";
                        for (var k = 0; k < response.filesInfo[i].group_2.length; ++k) {
                            htmlInfoMsg += "[" + k + "]: " + response.filesInfo[i].group_2[k];
                        }
                    }
                }
                $("div.calculator div#load-db-success span#load-success-message").html(htmlInfoMsg);
                $("div.calculator div#load-db-success").prop("hidden", false);
            } else {
                $("div.calculator div#load-db-success").prop("hidden", true);
                $("div.calculator div#load-db-alert span#load-alert-message")
                    .html("Ошибка!<br>Обратитесь в службу поддержки.<br>" + 
                          "Сообщение об ошибке:<br>" + response.error);
                $("div.calculator div#load-db-alert").prop("hidden", false);
            }
        },
        error:          function(xhr, status, error) {
            $("div.calculator div#load-db-alert span#load-alert-message")
                .html("Ошибка!<br>Обратитесь в службу поддержки.<br>Код ошибки: " + 
                      error.message + "<br>" + xhr.responseText);
            $("div.calculator div#load-db-alert").prop("hidden", false);
        }
    });
}