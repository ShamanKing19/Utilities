
class CustomPropertyTools
{
    addButton;
    removeButton;
    item;

    addButtonClass = 'property-item__add-button';

    removeButtonClass = 'property-item__remove-button';
    removeButtonSize = 24;
    removeButtonColor = '#586d7c';

    arrowUpClass = 'property-item__arrow-up';
    arrowDownClass = 'property-item__arrow-down';
    arrowColor = '#586d7c';
    arrowSize = 24;


    /**
     * Добавляет кнопку "Добавить" после указанного контейнера.
     *
     * Построение атрибута name инпута строится так: inputBaseName + [порядковый номер] + [последовательность ключей из data-key].
     * Чтобы сохранить ключи копируемых элементов, нужно указать их в data-key="KEY1;KEY2;KEY3;..."
     *
     * @param wrapperSelector класс контейнера, содержащего элементы
     * @param itemSelector класс элемента, который будет копироваться
     * @param inputBaseName базование значения атрибута name, к которому будет добавляться индекс в конце
     */
    setAddRowButton(wrapperSelector, itemSelector, inputBaseName) {
        const wrapper = document.querySelector(wrapperSelector);
        if(!wrapper) {
            console.error(wrapperSelector, 'not found!');
            return;
        }

        this.addButton = this.createAddButton();
        this.addButton.addEventListener('click', (e) => {
            const items = document.querySelectorAll(itemSelector);
            const itemsCount = items.length;
            const lastItem = items[itemsCount - 1];
            const lastItemIndex = this.getItemIndex(lastItem, inputBaseName);
            const newItem = lastItem.cloneNode(true);
            const inputs = newItem.querySelectorAll('select, input');
            const inputsCount = inputs.length;

            inputs.forEach((input, i) => {
                let newSelectName = `${inputBaseName}[${lastItemIndex + 1}]`;
                const key = input.dataset.key;
                if(key) {
                    const keys = key.split(';');
                    keys.forEach(keyName => {
                        newSelectName += `[${keyName}]`;
                    });
                } else if(inputsCount > 1) {
                    newSelectName += `[${i}]`;
                }

                input.setAttribute('name', newSelectName);
                input.value = '';
            });

            lastItem.after(newItem);
        });

        wrapper.after(this.addButton);
    }

    /**
     * Добавляет кнопки удаления для каждого элемента
     *
     * @param itemSelector
     */
    setRemoveRowButtons(itemSelector) {
        const items = document.querySelectorAll(itemSelector);
        if(items.length === 0) {
            console.error(itemSelector, 'not found!');
            return;
        }

        items.forEach(item => {
            const button = this.createRemoveButton();
            item.append(button);
        });

        $(document).on('click', itemSelector + ' .' + this.removeButtonClass, function(e) {
            const itemsCount = document.querySelectorAll(itemSelector).length;
            const item = this.closest(itemSelector);
            if(!item) {
                return;
            }

            if(itemsCount > 1) {
                item.remove();
            } else {
                const inputs = item.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.value = '';
                });
            }
        });
    }

    /**
     * Добавляет кнопки для осуществления сортировки
     *
     * @param itemSelector селектор элемента, который должен перемещаться
     */
    setSortButtons(itemSelector) {
        const items = document.querySelectorAll(itemSelector);

        items.forEach(item => {
            const arrowUpCopy = this.createUpArrow();
            const arrowDownCopy = this.createDownArrow();

            item.append(arrowUpCopy);
            item.append(arrowDownCopy);
        });

        $(document).on('click', itemSelector + ' .' + this.arrowUpClass, function() {
            const currentRow = this.closest(itemSelector);
            const previousRow = currentRow.previousElementSibling;

            if(!previousRow) {
                return;
            }

            currentRow.parentNode.insertBefore(currentRow, previousRow);
        });

        $(document).on('click', itemSelector + ' .' + this.arrowDownClass, function() {
            const currentRow = this.closest(itemSelector);
            const nextRow = currentRow.nextElementSibling;

            if(!nextRow) {
                return;
            }

            currentRow.parentNode.insertBefore(nextRow, currentRow);
        });

    }


    /**
     * Получение индекса элемента списка
     * @param {HTMLElement} listElement элемент списка, у которого нужно достать индекс
     * @param {String} inputBaseName базовое название инпута
     */
    getItemIndex(listElement, inputBaseName) {
        inputBaseName  = inputBaseName.replaceAll('[', '\\[');
        inputBaseName  = inputBaseName.replaceAll(']', '\\]');
        const input = listElement.querySelector('input, select');
        const name = input.getAttribute('name');
        const pattern = new RegExp(inputBaseName + '\\[(\\d*)\\]');
        const match = name.match(pattern);
        const index = Number(match[1]);
        if(index) {
            return index;
        }

        return 0;
    }


    createAddButton() {
        const button = document.createElement('button');
        button.className = this.addButtonClass;
        button.innerText = 'Добавить';
        button.setAttribute('type', 'button');
        return button;
    }


    createRemoveButton() {
        const button = document.createElement('span');
        button.className = this.removeButtonClass;
        const size = this.removeButtonSize;
        button.innerHTML = `
            <svg width="${size}px" height="${size}px" viewBox="0 0 ${size} ${size}" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" fill="${this.removeButtonColor}" d="M16.9498 8.46447C17.3404 8.07394 17.3404 7.44078 16.9498 7.05025C16.5593 6.65973 15.9261 6.65973 15.5356 7.05025L12.0001 10.5858L8.46455 7.05025C8.07402 6.65973 7.44086 6.65973 7.05033 7.05025C6.65981 7.44078 6.65981 8.07394 7.05033 8.46447L10.5859 12L7.05033 15.5355C6.65981 15.9261 6.65981 16.5592 7.05033 16.9497C7.44086 17.3403 8.07402 17.3403 8.46455 16.9497L12.0001 13.4142L15.5356 16.9497C15.9261 17.3403 16.5593 17.3403 16.9498 16.9497C17.3404 16.5592 17.3404 15.9261 16.9498 15.5355L13.4143 12L16.9498 8.46447Z"/>
            </svg>
        `;
        return button;
    }


    createUpArrow() {
        const arrow = document.createElement('span');
        arrow.className = this.arrowUpClass;
        arrow.innerHTML = `
            <svg width="${this.arrowSize}px" height="${this.arrowSize}px" viewBox="0 0 ${this.arrowSize} ${this.arrowSize}" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 7V17M12 7L16 11M12 7L8 11" stroke="${this.arrowColor}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        `;

        return arrow;
    }


    createDownArrow() {
        const arrow = document.createElement('span');
        arrow.className = this.arrowDownClass;
        arrow.innerHTML = `
            <svg width="${this.arrowSize}px" height="${this.arrowSize}px" viewBox="0 0 ${this.arrowSize} ${this.arrowSize}" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 17L12 7M12 17L8 13M12 17L16 13" stroke="${this.arrowColor}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        `;

        return arrow;
    }
}