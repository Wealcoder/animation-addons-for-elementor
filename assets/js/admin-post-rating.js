document.addEventListener('DOMContentLoaded', function () {
    const titleField = document.getElementById('title');
    if (titleField) {
        titleField.setAttribute('readonly', 'readonly');
        titleField.style.backgroundColor = '#f9f9f9';
    }
    const page_title = document.querySelector('.page-title-action');
    if(page_title){
        page_title.style.display = 'none';
    }
});

