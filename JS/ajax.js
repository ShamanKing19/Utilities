function sendFile() {
    const input = document.querySelector('.someTypeFileInput');

    if (window.FormData === undefined) return;
    if (input.files.length === 0) return;

    const formData = new FormData();
    formData.append('ACTION', 'LOAD_FILE'); // Сюда можно добавлять любые данные 
    formData.append('PRODUCT_IDS', [1, 2, 3]);  
    formData.append('FILE', input.files[0]); // Сюда грузится файл из input type=file

    $.ajax({
        type: 'POST',
        url: '/ajax/cart.php',
        dataType : 'json',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        success: function(response) {
            return response.data;
        },
        error: function (error) {
            return error;
        }
    });
}