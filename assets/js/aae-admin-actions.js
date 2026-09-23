/**
 * The "Import Page" action, injected next to WordPress's own "Add Page" button.
 *
 * The button's LOOK is owned by inc/admin/page-import.php::heading_button_css(),
 * and every rule there is keyed on the `aae-import-page-action` class -- so that
 * class is what makes the stylesheet apply at all. Without it the button falls
 * back to core's pale `.page-title-action` background while still carrying white
 * label text, which is how it shipped: unreadable.
 *
 * Nothing is styled inline here, deliberately. Two of the inline rules it used to
 * set also misaligned it against "Add Page": core positions `.page-title-action`
 * at `top: -3px`, and `top: 0` pushed ours three pixels lower.
 *
 * The label comes from AAE_PAGE_IMPORT.label (translated in PHP). A string
 * written in this file cannot be translated.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof AAE_PAGE_IMPORT === 'undefined') {
        return;
    }

    const heading = document.querySelector('.wrap .wp-heading-inline + .page-title-action');

    if (!heading) {
        return;
    }

    const btn = document.createElement('a');
    btn.id = 'aae-heading-button';
    btn.className = 'page-title-action aae-import-page-action';
    btn.href = AAE_PAGE_IMPORT.page_url;

    const icon = document.createElement('img');
    icon.src = AAE_PAGE_IMPORT.logo;
    icon.alt = '';

    const label = document.createElement('span');
    label.textContent = AAE_PAGE_IMPORT.label;

    btn.appendChild(icon);
    btn.appendChild(label);

    heading.after(btn);
});
