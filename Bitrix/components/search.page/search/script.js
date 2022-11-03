function submitForm() {
    const input = document.querySelector('input[name="q"]');
    const submitButton = document.querySelector('#submit-form');

    let timeout;
    input.addEventListener('keyup', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout( () => {
            submitButton.click();
        }, 500);
    });
}

function focusOnSearch() {
    const input = document.querySelector('input[name="q"]');
    input.selectionStart = input.selectionEnd = input.value.length;
    input.focus();
}