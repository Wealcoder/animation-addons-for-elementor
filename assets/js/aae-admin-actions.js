   document.addEventListener('DOMContentLoaded', function() {
            const heading = document.querySelector('.wrap .wp-heading-inline + .page-title-action');
     
            if (heading) {
                const btn = document.createElement('a');
                btn.href = AAE_ADMIN.page_url;

                btn.id = 'aae-heading-button';
                btn.className = 'page-title-action'; // same styling as Add New
                btn.innerText = 'Import Page';
                heading.after(btn);
            }
});