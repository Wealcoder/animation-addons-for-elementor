/**
 * Pages list: adds an "Import Page" action next to "Add Page".
 * Styling lives in AAE_Admin_Page_Importer::heading_button_css() (inline admin CSS);
 * this script only builds the markup so the button reads like a native action.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof AAE_PAGE_IMPORT === 'undefined') {
        return;
    }

    const addNew = document.querySelector('.wrap .wp-heading-inline + .page-title-action');
    if (!addNew || document.getElementById('aae-heading-button')) {
        return;
    }

    const btn = document.createElement('a');
    btn.href = AAE_PAGE_IMPORT.page_url;
    btn.id = 'aae-heading-button';
    btn.className = 'page-title-action aae-import-page-action';

    const icon = document.createElement('img');
    icon.src = AAE_PAGE_IMPORT.logo;
    icon.alt = '';
    icon.width = 16;
    icon.height = 16;
    icon.setAttribute('aria-hidden', 'true');

    const label = document.createElement('span');
    label.textContent = AAE_PAGE_IMPORT.label || 'Import Page';

    btn.appendChild(icon);
    btn.appendChild(label);
    addNew.after(btn);
});
