class ItemsList
{
    hiddenClass = 'item-list__hidden';

    /**
     * @param {Number} currentPage Номер текущей страницы
     */
    constructor(currentPage) {
        this.currentPage = currentPage;
    }

    /**
     * Инициализация функционала кнопки "Показать ещё"
     *
     * @param wrapperClass Класс контейнера с элементами
     * @param itemClass Класс элемента
     * @param buttonClass Класс кнопки
     * @param pageAttributeName Название data-атрибута, в котором хранится номер следующей страницы
     * @param urlPageParamName Название переменной пагинации в ссылке
     */
    initShowMoreButton(wrapperClass, itemClass, buttonClass, pageAttributeName, urlPageParamName) {
        const wrapper = document.querySelector(`.${wrapperClass}`);
        if(!wrapper) {
            console.error(`Не найден контейнер с классом "${wrapperClass}"`);
            return;
        }

        // TODO: Находить ближайшую кнопку (если будет несколько компонентов на странице, оно сломается)
        const button = document.querySelector(`.${buttonClass}`);
        if(!button) {
            console.error(`Не найдено кнопки с классом "${buttonClass}"`);
            return;
        }

        const items = wrapper.querySelectorAll(`.${itemClass}`);
        if(!items) {
            console.error(`Не найдено элементов с классом "${itemClass}"`);
            return;
        }

        button.addEventListener('click' ,async (e) => {
            button.classList.add(this.hiddenClass);

            const nextPage = Number(button.dataset[pageAttributeName]);
            if(!nextPage) {
                console.error(`Атрибут "data-${pageAttributeName}" не найден`);
                return;
            }

            const url = new URL(location.href);
            url.searchParams.set(urlPageParamName, nextPage.toString());
            const response = await this.sendAjax(url.href, {}, 'get');
            if(!response) {
                return;
            }

            const html = document.createElement('div');
            html.innerHTML = response;

            const nextPageWrapper = html.querySelector(`.${wrapperClass}`);
            if(!nextPageWrapper) {
                return;
            }

            const nextPageItems = nextPageWrapper.querySelectorAll(`.${itemClass}`);
            if(nextPageItems.length === 0) {
                return;
            }

            // Подстановка элементов в конец контейнера
            nextPageItems.forEach(item => wrapper.appendChild(item));

            // Перемещение кнопки
            button.dataset[pageAttributeName] = nextPage + 1;
            button.classList.remove(this.hiddenClass);
        })
    }

    /**
     * Отправка ajax запроса
     *
     * @param {String} path путь до ajax обработчика
     * @param {Object} data данные
     * @param {String} method тип запроса
     * @return {Promise<*>}
     */
    async sendAjax(path, data, method = 'post') {
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
    async sendAjaxForm(path, formData, method = 'post') {
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
}