/**
 * Отправка ajax запроса
 *
 * @param {String} path путь до ajax обработчика
 * @param {Object} data данные
 * @param {String} method тип запроса
 * @return {Promise<*>}
 */
window.sendAjax = async function(path, data, method = 'post') {
    let errorResponse;
    try {
        return await $.ajax({
            url: path,
            method: method,
            data: data,
            success: function(response) {
                return response.data;
            },
            error: function(response, status, error) {
                errorResponse = response.responseJSON;
            }
        });
    } catch (e) {
        return errorResponse;
    }
}


/**
 * Отправка данных формы по ajax
 *
 * @param {String} path путь до ajax обработчика
 * @param {FormData} formData данные формы
 * @param {String} method тип запроса
 * @return {Promise<*>}
 */
window.sendAjaxForm = async function(path, formData, method = 'post') {
    let errorResponse;
    try {
        return $.ajax({
            url: path,
            method: method,
            contentType: false,
            processData: false,
            data: formData,
            success: function(response) {
                return response.data;
            },
            error: function(response, status, error) {
                errorResponse = response.responseJSON;
            }
        });
    } catch (e) {
        return errorResponse;
    }
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


function setCookie(name, value, options = {path: '/'}) {
    if (!name) {
        return;
    }

    options = options || {};

    if (options.expires instanceof Date) {
        options.expires = options.expires.toUTCString();
    }

    if (value instanceof Object) {
        value = JSON.stringify(value);
    }
    let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);
    for (let optionKey in options) {
        updatedCookie += "; " + optionKey;
        let optionValue = options[optionKey];
        if (optionValue !== true) {
            updatedCookie += "=" + optionValue;
        }
    }

    document.cookie = updatedCookie;
}

function deleteCookie(name) {
    setCookie(name, null, {
        expires: new Date(),
        path: '/'
    })
}
