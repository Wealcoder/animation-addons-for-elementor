document.addEventListener('DOMContentLoaded', function () {
    const heading = document.querySelector('.wrap .wp-heading-inline + .page-title-action');

    if (heading) {
        const btn = document.createElement('a');
        btn.href = AAE_PAGE_IMPORT.page_url;
        btn.style.top = "0";
        btn.style.left = "5px";
        btn.style.border = "1px solid #FCCBC0";
        btn.style.borderRadius = "6px";
        btn.style.padding = "0 8px";
        btn.id = 'aae-heading-button';
        btn.className = 'page-title-action'; // same styling as Add New
        // btn.innerText = 'Import Page';
        btn.innerHTML = `
                <div style="display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    gap: 6px; 
                "><img src="${AAE_PAGE_IMPORT.logo}" /> <span style="font-size: 12px; font-weight: 500; color: #FFFFFF">import page</span></div>
                `;
        heading.after(btn);
    }
});