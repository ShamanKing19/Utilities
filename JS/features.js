
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