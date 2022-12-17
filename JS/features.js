
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
 * @param {Function} func   Функция, которая вызовется через pauseTimeMs
 * @param {Int} pauseTimeMs Пауза
 */
function debounce(func, pauseTimeMs) {
    let timeout;
    const item = document.querySelector('.some-class');
    item.addEventListener('keyup', async function (e) {
        clearTimeout(timeout);
        timeout = await setTimeout( async() => {
            func();
        }, pauseTimeMs);
    });
}