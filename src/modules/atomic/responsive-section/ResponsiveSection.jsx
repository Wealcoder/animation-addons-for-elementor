/* eslint-env browser */

import * as React from 'react';
import { Stack, Tabs, Tab, Box } from '@elementor/ui';
import { useSelectedElementSettings } from '@elementor/editor-elements';
import { useActiveBreakpoint } from '@elementor/editor-responsive';

import { ResponsiveRow } from './ResponsiveRow';

/**
 * Section body — replaces the placeholder anchor row Elementor would have
 * rendered. Receives the section config (anchorKey + fields[]) and renders
 * one <ResponsiveRow> per field whose `when` predicate (if any) evaluates
 * truthy against current settings + active breakpoint.
 *
 * Subscribes to:
 *   - useSelectedElementSettings: re-renders on every settings mutation, so
 *     predicate-gated fields appear/disappear live as the user edits.
 *   - useActiveBreakpoint:        re-renders on device-mode switch, so
 *     per-bp predicates (e.g. effect=none on tablet) take effect instantly.
 *
 * If no element is selected (deselect → empty panel) we render nothing —
 * Elementor's panel chrome handles the empty state.
 */
export function ResponsiveSection({ config }) {
	const { element, settings } = useSelectedElementSettings();
	const activeBp = useActiveBreakpoint() || 'desktop';
	
	const [activeTab, setActiveTab] = React.useState(0);

	if (!element || !settings) return null;

	const { fields = [], bindPrefix = '' } = config;

	// Extract unique tab labels, defaulting to 'Content'
	const tabs = Array.from(new Set(fields.map((f) => f.tab || 'Content')));
	const hasMultipleTabs = tabs.length > 1;

	return (
		<Stack direction="column" sx={{ width: '100%' }}>
			{hasMultipleTabs && (
				<Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 2 }}>
					<Tabs 
						value={activeTab} 
						onChange={(e, newVal) => setActiveTab(newVal)}
						variant="fullWidth"
					>
						{tabs.map((tabLabel, idx) => (
							<Tab key={idx} label={tabLabel} />
						))}
					</Tabs>
				</Box>
			)}

			{fields.map((field) => {
				// Filter fields by active tab
				const fieldTab = field.tab || 'Content';
				if (hasMultipleTabs && tabs.indexOf(fieldTab) !== activeTab) {
					return null;
				}

				// Per-section bindPrefix keeps each field row short — e.g.
				// `bind: 'effect'` becomes the full prop key `aae_anim_effect`
				// when this section sets bindPrefix: 'aae_anim_'. Falls back
				// to the bare bind for sections that don't set a prefix.
				const fullBind = field.bind ? `${bindPrefix}${field.bind}` : null;

				if (typeof field.when === 'function' && !field.when(settings, activeBp)) {
					return null;
				}
				return (											
						<ResponsiveRow
							key={fullBind || field.control}
							bind={fullBind}
							label={field.label}
							control={field.control}
							options={field.options}
							placeholder={field.placeholder}
							min={field.min}
							max={field.max}
							step={field.step}
							units={field.units}
							defaultUnit={field.defaultUnit}
							cells={field.cells}
							addLabel={field.addLabel}
							rowDefaults={field.rowDefaults}
							defaultValue={field.defaultValue}
							responsive={field.responsive !== false}
							propValue={fullBind ? (settings[fullBind] ?? null) : null}
							activeBp={activeBp}
							elementId={element.id}
							play_group={field?.play_group}
						/>					
				);
			})}
		</Stack>
	);
}
