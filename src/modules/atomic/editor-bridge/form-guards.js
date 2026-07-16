/* eslint-env browser */

/**
 * Form guards — save-time sanity warnings for AAE Forms (spec hard rules):
 *
 *  1. "A form must never become silently unsubmittable" — warn when an
 *     e-aae-a-form contains no submit button.
 *  2. "Nested e-aae-a-form inside another e-aae-a-form must be rejected or
 *     warned at save time" — warn on nesting (the backend Schema_Walker
 *     already refuses to blend nested forms; this makes it visible).
 *
 * Hooks `$e.commands.on('run:after')` for 'document/save/save' (the funnel
 * every draft/update/publish goes through), walks the live container tree,
 * and surfaces problems via the editor toast. Warnings only — the save
 * itself is never blocked (a lead-losing save is still better than lost
 * work, and the dashboard's Form Health tab shows the same issues).
 */

import { __ } from '@wordpress/i18n';
import { track } from './disposables';

const FORM_TYPE = 'e-aae-a-form';
const SUBMIT_TYPE = 'e-aae-a-form-submit';

const typeOf = (container) => {
	const model = container?.model;
	if (!model?.get) {
		return '';
	}
	const elType = model.get('elType');
	return 'widget' === elType ? (model.get('widgetType') || '') : (elType || '');
};

const childrenOf = (container) => {
	const children = container?.children;
	if (!children) {
		return [];
	}
	// v1 containers keep children as a plain array; stay defensive about
	// array-likes from older editor versions.
	return Array.from(children);
};

/** Depth-first over a subtree. visit() gets (container, type). */
const walk = (container, visit) => {
	visit(container, typeOf(container));
	childrenOf(container).forEach((child) => walk(child, visit));
};

/** { missingSubmit: string[], nested: string[] } — offending form titles. */
export function auditForms(root) {
	const missingSubmit = [];
	const nested = [];

	const auditForm = (formContainer) => {
		let hasSubmit = false;
		let hasNestedForm = false;

		childrenOf(formContainer).forEach((child) =>
			walk(child, (descendant, type) => {
				if (SUBMIT_TYPE === type) {
					hasSubmit = true;
				}
				if (FORM_TYPE === type) {
					hasNestedForm = true;
				}
			})
		);

		const title = formContainer.model?.get?.('title') || 'AAE Form';
		if (!hasSubmit) {
			missingSubmit.push(title);
		}
		if (hasNestedForm) {
			nested.push(title);
		}
	};

	walk(root, (container, type) => {
		if (FORM_TYPE === type) {
			auditForm(container);
		}
	});

	return { missingSubmit, nested };
}

const toast = (message) => {
	const notifications = window.elementor?.notifications;
	if (notifications?.showToast) {
		notifications.showToast({ message, sticky: false });
	}
};

const runAudit = () => {
	const root = window.elementor?.documents?.getCurrent?.()?.container;
	if (!root) {
		return;
	}

	const { missingSubmit, nested } = auditForms(root);

	if (missingSubmit.length) {
		toast(
			__('AAE Form has no Submit button — visitors cannot submit it. Add a Submit widget from the panel.', 'animation-addons-for-elementor')
		);
	}
	if (nested.length) {
		toast(
			__('An AAE Form is nested inside another AAE Form — the inner form will be ignored. Move it outside.', 'animation-addons-for-elementor')
		);
	}
};

export function startFormGuards() {
	const $e = window.$e;
	if (!$e?.commands?.on) {
		return;
	}

	const onAfter = (component, command) => {
		if ('document/save/save' === command) {
			// After the save settles — the toast shouldn't race the editor's
			// own "changes saved" feedback.
			setTimeout(runAudit, 100);
		}
	};

	$e.commands.on('run:after', onAfter);
	track(() => $e.commands.off?.('run:after', onAfter));
}
