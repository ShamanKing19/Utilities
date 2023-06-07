/**
 * Отправка ajax запроса
 *
 * @param {String} path путь до ajax обработчика
 * @param {Object} data данные
 * @param {String} method тип запроса
 * @return {Promise<*>}
 */
async function sendAjax(path, data, method = 'post') {
    return $.ajax({
        url: path,
        method: method,
        data: data,
        success: function(response) {
            return response.data;
        },
        error: function(error) {
            return false;
        }
    });
}


/**
 * Отправка данных формы по ajax
 *
 * @param {String} path путь до ajax обработчика
 * @param {FormData} formData данные формы
 * @param {String} method тип запроса
 * @return {Promise<*>}
 */
async function sendAjaxForm(path, formData, method = 'post') {
    return $.ajax({
        url: path,
        method: method,
        contentType: false,
        processData: false,
        data: formData,
        success: function(response) {
            return response.data;
        },
        error: function(error) {
            return false;
        }
    });
}


/**
 * Запускает функцию с задержкой с защитой от повторного вызова
 *
 * @param {Node} element элемент, на который вешается событие
 * @param {String} event событие, при котором срабатывает передаваемая функция
 * @param {Number} pauseTimeMs пауза
 * @param {Function} func функция, которая вызовется через pauseTimeMs
 */
function debounce(element, event, pauseTimeMs, func) {
    let timeout;
    element.addEventListener(event, async function (e) {
        clearTimeout(timeout);
        timeout = await setTimeout( async() => {
            func();
        }, pauseTimeMs);
    });
}
