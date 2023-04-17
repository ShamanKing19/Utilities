
/**
 * Нахождение всех элементов, следующих за переданным в параметре
 * 
 * @param {Node} node       Элемент, с которого начинается поиск
 * @return {Array<Node>}    Массив последующих элементов
 */
function getElementSiblings(node);
    const result = [];
    let node = document.querySelector('.firstNode');

    while (node) {
        if (node !== this && node.nodeType === Node.ELEMENT_NODE)
            result.push(node);
        node = node.nextElementSibling || node.nextSibling;
    }

    return result;


/**
 * Запускает функцию с задержкой с защитой от повторного вызова
 * 
 * @param {Node} element    Элемент, на который вешается событие
 * @param {String} event    Событие, при котором срабатывает передаваемая функция
 * @param {Number} pauseTimeMs Пауза
 * @param {Function} func   Функция, которая вызовется через pauseTimeMs
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