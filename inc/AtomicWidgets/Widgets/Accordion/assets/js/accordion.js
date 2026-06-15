import { register } from '@elementor/frontend-handlers';

const initAccordion = (container) => {
    const items = container.querySelectorAll('.aae-a-accordion-item');
    if (!items.length) return;

    const maxItemsExpanded = container.dataset.maxItemsExpanded || 'one';
    const defaultState = container.dataset.defaultState || 'first';

    items.forEach((item) => {
        const header = item.querySelector('.aae-accordion-header');
        const content = item.querySelector('.aae-accordion-content');
        if (!header || !content) return;

        // Handle defaultState 'first' if this is the first item
        const isFirstItem = item === items[0];
        if (defaultState === 'first' && isFirstItem && !item.classList.contains('active')) {
            item.classList.add('active');
            header.setAttribute('aria-expanded', 'true');
        } else if (defaultState === 'none' && item.classList.contains('active')) {
            item.classList.remove('active');
            header.setAttribute('aria-expanded', 'false');
        }

        header.addEventListener('click', () => {
            const isActive = item.classList.contains('active');

            // Close other items if maxItemsExpanded is 'one'
            if (maxItemsExpanded === 'one' && !isActive) {
                items.forEach((otherItem) => {
                    if (otherItem !== item && otherItem.classList.contains('active')) {
                        otherItem.classList.remove('active');
                        otherItem.querySelector('.aae-accordion-header').setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // Toggle current item
            if (isActive) {
                item.classList.remove('active');
                header.setAttribute('aria-expanded', 'false');
            } else {
                item.classList.add('active');
                header.setAttribute('aria-expanded', 'true');
            }
        });
    });
};

register({
    elementType: 'e-aae-a-accordion',
    id: 'aae-a-accordion-handler',
    callback: ({ element }) => {
        const container = element.classList.contains('aae-a-accordion') ? element : element.querySelector('.aae-a-accordion');
        if (container) initAccordion(container);
    }
});
