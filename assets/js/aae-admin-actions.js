document.addEventListener('DOMContentLoaded', function () {
    const heading = document.querySelector('.wrap .wp-heading-inline + .page-title-action');

    if (heading) {
        const btn = document.createElement('a');
        btn.href = AAE_PAGE_IMPORT.page_url;
        btn.style.top = "2px";
        btn.style.left = "5px";
        btn.style.borderColor = "#fc6848";
        btn.id = 'aae-heading-button';
        btn.className = 'page-title-action'; // same styling as Add New
        // btn.innerText = 'Import Page';
        btn.innerHTML = `
                <div style="display: flex; justify-content: center; align-items: center; gap: 9px"><img src="${AAE_PAGE_IMPORT.logo}" /> <span style="font-wight: 500; color: #fc6848">AAE Import</span></div>
                `;
        heading.after(btn);
    }
});