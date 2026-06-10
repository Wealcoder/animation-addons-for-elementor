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
function InnerTabsGroup({ fields, settings, activeBp, element, bindPrefix }) {
	const [activeTab, setActiveTab] = React.useState(0);
	const currentTab = activeTab >= fields.length ? 0 : activeTab;
	
	return (
		<Box sx={{ width: '100%', mb: 2, border: '1px solid', borderColor: 'divider', borderRadius: 1, overflow: 'hidden' }}>
			<Box sx={{ borderBottom: 1, borderColor: 'divider', bgcolor: 'transparent' }}>
				<Tabs value={currentTab} onChange={(e, v) => setActiveTab(v)} variant="fullWidth" sx={{ minHeight: 36 }}>
					{fields.map((f, i) => (
						<Tab key={i} label={f.innerTabLabel || f.label} sx={{ minHeight: 36, py: 0.5, fontSize: '0.75rem', textTransform: 'none', fontWeight: 600 }} />
					))}
				</Tabs>
			</Box>
			<Box sx={{ p: 1.5, pb: 0.5 }}>
				{fields.map((field, i) => {
					if (i !== currentTab) return null;
					const fullBind = field.bind ? `${bindPrefix}${field.bind}` : null;
					return (
						<ResponsiveRow
							key={fullBind || field.control}
							bind={fullBind}
							label={typeof field.label === 'function' ? field.label(settings, activeBp) : field.label}
							control={field.control}
							options={field.options}
							placeholder={field.placeholder}
							min={field.min}
							max={field.max}
							step={field.step}
							units={field.units}
							defaultUnit={field.defaultUnit}
							datalist={field.datalist}
							cells={field.cells}
							addLabel={field.addLabel}
							rowDefaults={field.rowDefaults}
							defaultValue={field.defaultValue}
							responsive={field.responsive !== false}
							propValue={fullBind ? (settings[fullBind] ?? null) : null}
							activeBp={activeBp}
							elementId={element.id}
							play_group={field?.play_group}
							live_change={field.live_change}
							help={field.help}
							settings={settings}
						/>
					);
				})}
			</Box>
		</Box>
	);
}

export function ResponsiveSection({ config }) {
	const { element, settings } = useSelectedElementSettings();
	const activeBp = useActiveBreakpoint() || 'desktop';
	
	if (!element || !settings) return null;

	const { fields = [], bindPrefix = '' } = config;

	const elements = [];
	let currentGroup = null;
	let groupFields = [];

	const flushGroup = () => {
		if (groupFields.length > 0) {
			elements.push(
				<InnerTabsGroup
					key={`group-${currentGroup}`}
					groupName={currentGroup}
					fields={groupFields}
					settings={settings}
					activeBp={activeBp}
					element={element}
					bindPrefix={bindPrefix}
				/>
			);
			groupFields = [];
			currentGroup = null;
		}
	};

	fields.forEach((field) => {
		if (typeof field.when === 'function' && !field.when(settings, activeBp)) {
			return;
		}

		const fieldGroup = typeof field.innerTabGroup === 'function' 
			? field.innerTabGroup(settings, activeBp) 
			: field.innerTabGroup;

		if (fieldGroup) {
			if (currentGroup !== fieldGroup) {
				flushGroup();
				currentGroup = fieldGroup;
			}
			groupFields.push(field);
		} else {
			flushGroup();
			const fullBind = field.bind ? `${bindPrefix}${field.bind}` : null;
			const label = typeof field.label === 'function' ? field.label(settings, activeBp) : field.label;
			elements.push(
				<ResponsiveRow
					key={fullBind || field.control}
					bind={fullBind}
					label={label}
					control={field.control}
					options={field.options}
					placeholder={field.placeholder}
					min={field.min}
					max={field.max}
					step={field.step}
					units={field.units}
					defaultUnit={field.defaultUnit}
					datalist={field.datalist}
					cells={field.cells}
					addLabel={field.addLabel}
					rowDefaults={field.rowDefaults}
					defaultValue={field.defaultValue}
					responsive={field.responsive !== false}
					propValue={fullBind ? (settings[fullBind] ?? null) : null}
					activeBp={activeBp}
					elementId={element.id}
					play_group={field?.play_group}
					live_change={field.live_change}
					help={field.help}
					settings={settings}
				/>
			);
		}
	});
	flushGroup();

	return (
		<Stack direction="column" sx={{ width: '100%' }}>
			{elements}
		</Stack>
	);
}
