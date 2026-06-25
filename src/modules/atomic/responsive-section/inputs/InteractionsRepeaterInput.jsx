/* eslint-env browser */

import * as React from 'react';
import {
	Autocomplete,
	Box,
	Collapse,
	IconButton,
	Slider,
	Stack,
	Switch,
	TextField,
	Tooltip,
	Typography,
} from '@elementor/ui';

import { RepeaterInput } from './RepeaterInput';

// Play-progress ring keyframe (injected once). Sweeps the arc empty→full by
// animating stroke-dashoffset down to 0. Circumference = 2π·10.5 ≈ 65.97.
const RING_CIRCUMFERENCE = 2 * Math.PI * 10.5;
if (typeof document !== 'undefined' && !document.getElementById('aae-play-ring-style')) {
	const style = document.createElement('style');
	style.id = 'aae-play-ring-style';
	style.textContent = `@keyframes aaePlayRing { from { stroke-dashoffset: ${RING_CIRCUMFERENCE}; } to { stroke-dashoffset: 0; } }`;
	document.head.appendChild(style);
}

/**
 * InteractionsRepeaterInput — the repeater whose every row is a *full*
 * animation interaction (trigger + effect + all config), not a flat
 * property/value pair. Mirrors Elementor's "Interactions" panel UX.
 *
 * Generic across systems (text / regular / image). The per-row field set
 * is described by `rowFields` (passed from the section config), so this
 * component owns NO effect-specific knowledge — it just renders fields and
 * round-trips a plain rows array.
 *
 * value      : Array<rowData>           rowData = flat { trigger, effect, ... }
 * onChange   : (nextRows) => void
 * rowFields  : Array<fieldDef>          one entry per field inside a row
 * rowDefaults: object                   seed for a freshly-added row
 * addLabel   : string
 *
 * fieldDef shape (a subset of the section config field):
 *   { bind, label, control, options?, placeholder?, min?, max?, step?,
 *     defaultValue?, datalist?, when?(rowData, activeBp),
 *     cells?, addLabel?, rowDefaults? }   // cells/addLabel for nested repeater
 *
 * Exclusive triggers: 'on_page_load' is max 1 per element; 'on_scroll' and
 * 'play_with_scroll' share a single slot (one of them, max 1). When a row
 * already uses one, the trigger dropdown in *other* rows hides ALL the
 * exclusive options. 'click' / 'mouseover' are always unlimited.
 *
 * Rule: there are TWO independent exclusive groups, each capped at one row per
 * element:
 *   - Group A (page-load): on_page_load — max 1
 *   - Group B (scroll-ish): on_scroll + play_with_scroll + on_slide_change —
 *     max 1 between them
 * So an element may carry one page-load row AND one scroll/play-scroll/
 * slide-change row simultaneously. Click and hover are unlimited.
 */

export const EXCLUSIVE_GROUPS = [
	['on_page_load'],
	['on_scroll', 'play_with_scroll', 'on_slide_change'],
];
// Flat list of every exclusive trigger (used by callers that just need
// membership, e.g. "is this trigger exclusive at all").
export const EXCLUSIVE_TRIGGERS = EXCLUSIVE_GROUPS.flat();

/** Which exclusive options are unavailable to row `selfIndex`. For each
 *  exclusive group, if ANY other row already uses a trigger from that group,
 *  every trigger in that group is blocked here (one slot per group). The two
 *  groups are independent, so a page-load row doesn't block a scroll row. */
function takenExclusivesExcluding(rows, selfIndex) {
	const taken = new Set();
	for (const group of EXCLUSIVE_GROUPS) {
		const usedByOther = rows.some((r, i) => i !== selfIndex && group.includes(r?.trigger));
		if (usedByOther) {
			group.forEach((t) => taken.add(t));
		}
	}
	return taken;
}

/* ---------- per-row field cells ---------- */

function FieldSelect({ field, value, onChange, disabledOptions, rowData }) {
	// options may be a static array OR a (rowData) => array function, so a
	// field's choices can depend on other row values (e.g. the Method dropdown
	// only offers "Set" when the effect is custom).
	let options = typeof field.options === 'function' ? (field.options(rowData) || []) : (field.options || []);
	if (disabledOptions && disabledOptions.size) {
		options = options.filter((o) => !disabledOptions.has(o.value));
	}
	const selected = options.find((o) => o.value === value) || null;
	return (
		<Autocomplete
			size="tiny"
			fullWidth
			options={options}
			value={selected}
			isOptionEqualToValue={(opt, val) => opt.value === val.value}
			getOptionLabel={(opt) => opt.label || String(opt.value)}
			onChange={(_, next) => onChange(next ? next.value : '')}
			ListboxProps={{ style: { maxHeight: 300 } }}
			renderInput={(params) => (
				<TextField {...params} size="tiny" placeholder={field.placeholder || ''} />
			)}
		/>
	);
}

function FieldText({ field, value, onChange }) {
	const list = field.datalist;
	const options = Array.isArray(list)
		? list.map((o) => (typeof o === 'string' ? o : o.value))
		: null;
	if (options) {
		return (
			<Autocomplete
				size="tiny"
				fullWidth
				freeSolo
				options={options}
				value={value ?? ''}
				onChange={(_, next) => onChange(next || '')}
				renderInput={(params) => (
					<TextField
						{...params}
						size="tiny"
						placeholder={field.placeholder || ''}
						onChange={(e) => onChange(e.target.value)}
					/>
				)}
			/>
		);
	}
	return (
		<TextField
			size="tiny"
			fullWidth
			value={value ?? ''}
			placeholder={field.placeholder || ''}
			onChange={(e) => onChange(e.target.value)}
		/>
	);
}

function FieldNumber({ field, value, onChange }) {
	return (
		<TextField
			size="tiny"
			fullWidth
			type="number"
			value={value ?? ''}
			placeholder={field.placeholder || ''}
			inputProps={{ min: field.min, max: field.max, step: field.step ?? 'any' }}
			onChange={(e) => {
				const raw = e.target.value;
				if (raw === '') return onChange(null);
				const num = Number(raw);
				onChange(Number.isFinite(num) ? num : null);
			}}
		/>
	);
}

function FieldSlider({ field, value, onChange }) {
	const { min = 0, max = 10, step = 0.1 } = field;
	const num = typeof value === 'number' ? value : (Number(value) || min);
	return (
		<Stack direction="row" alignItems="center" spacing={1}>
			<Slider
				size="small"
				value={num}
				min={min}
				max={max}
				step={step}
				onChange={(_, v) => onChange(v)}
				sx={{ flex: 1 }}
			/>
			<TextField
				size="tiny"
				type="number"
				value={value ?? ''}
				inputProps={{ min, max, step }}
				onChange={(e) => {
					const raw = e.target.value;
					if (raw === '') return onChange(null);
					const n = Number(raw);
					onChange(Number.isFinite(n) ? n : null);
				}}
				sx={{ width: 64 }}
			/>
		</Stack>
	);
}

function FieldSwitch({ value, onChange }) {
	return (
		<Box sx={{ display: 'flex', justifyContent: 'flex-start' }}>
			<Switch size="small" checked={!!value} onChange={(_, c) => onChange(c)} />
		</Box>
	);
}

/**
 * Render one field inside a row. Nested repeater (control='repeater') is
 * delegated to the generic RepeaterInput — its rows live under
 * rowData[field.bind] as a plain array (no per-bp envelope; the WHOLE
 * interactions list is already per-bp at the outer level).
 */
function RowField({ field, rowData, activeBp, onFieldChange, triggerDisabledOptions }) {
	const value = rowData?.[field.bind];

	let input;
	switch (field.control) {
		case 'select':
			input = (
				<FieldSelect
					field={field}
					value={value}
					rowData={rowData}
					onChange={(v) => onFieldChange(field.bind, v)}
					disabledOptions={field.bind === 'trigger' ? triggerDisabledOptions : null}
				/>
			);
			break;
		case 'text':
			input = <FieldText field={field} value={value} onChange={(v) => onFieldChange(field.bind, v)} />;
			break;
		case 'number':
			input = <FieldNumber field={field} value={value} onChange={(v) => onFieldChange(field.bind, v)} />;
			break;
		case 'slider':
			input = <FieldSlider field={field} value={value} onChange={(v) => onFieldChange(field.bind, v)} />;
			break;
		case 'switch':
			input = <FieldSwitch value={value} onChange={(v) => onFieldChange(field.bind, v)} />;
			break;
		case 'repeater':
			input = (
				<RepeaterInput
					value={Array.isArray(value) ? value : []}
					onChange={(rows) => onFieldChange(field.bind, rows)}
					cells={field.cells}
					addLabel={field.addLabel || 'Add'}
					rowDefaults={field.rowDefaults || {}}
					settings={rowData}
					activeBp={activeBp}
				/>
			);
			break;
		default:
			input = <FieldText field={field} value={value} onChange={(v) => onFieldChange(field.bind, v)} />;
	}

	const label = typeof field.label === 'function' ? field.label(rowData, activeBp) : field.label;

	return (
		<Stack direction="column" sx={{ width: '100%', mb: 1 }}>
			{label && (
				<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5 }}>
					{label}
				</Typography>
			)}
			{input}
		</Stack>
	);
}

/* ---------- summary line for a collapsed row ---------- */

function rowSummary(rowData, rowFields) {
	const triggerField = rowFields.find((f) => f.bind === 'trigger');
	const effectField = rowFields.find((f) => f.bind === 'effect');
	const labelFor = (field, val) => {
		if (!field) return val;
		const opts = typeof field.options === 'function' ? (field.options(rowData) || []) : (field.options || []);
		const opt = opts.find((o) => o.value === val);
		return opt ? opt.label : val;
	};
	const trig = labelFor(triggerField, rowData?.trigger) || '—';
	const eff = labelFor(effectField, rowData?.effect) || '—';
	return `${trig} → ${eff}`;
}

/* ---------- one collapsible interaction card ---------- */

function InteractionCard({
	rowData,
	rowFields,
	index,
	activeBp,
	rows,
	onChange,
	onRemove,
	onPlay,
}) {
	const [open, setOpen] = React.useState(false);

	// Play-progress ring: each ▶ click bumps playKey, which restarts a CSS
	// keyframe that sweeps an SVG ring from empty→full over roughly the
	// interaction's own (delay + duration) window. Pure visual feedback.
	const [playKey, setPlayKey] = React.useState(0);
	const [playing, setPlaying] = React.useState(false);
	const doneTimer = React.useRef(0);

	React.useEffect(() => () => clearTimeout(doneTimer.current), []);

	const sec = (v, d) => {
		const n = Number(v);
		return Number.isFinite(n) && n >= 0 ? n : d;
	};

	// Total ring window (s), clamped so very long / short tweens still read well.
	const ringDurMs = Math.min(
		6000,
		Math.max(400, (sec(rowData?.delay, 0.15) + sec(rowData?.duration, 1)) * 1000)
	);

	const handlePlayClick = (e) => {
		e.stopPropagation();
		onPlay(index);
		setPlayKey((k) => k + 1);
		setPlaying(true);
		clearTimeout(doneTimer.current);
		doneTimer.current = setTimeout(() => setPlaying(false), ringDurMs + 150);
	};

	const onFieldChange = (bind, val) => {
		const next = rows.slice();
		let newRow = { ...(next[index] || {}), [bind]: val };

		// A field may carry an `onSet(rowData, newValue) -> patch` hook that
		// returns extra fields to merge (e.g. selecting an effect fills the
		// custom-props rows + sets method). Patch is applied on top of the
		// single-field change.
		const fieldDef = rowFields.find((f) => f.bind === bind);
		if (fieldDef && typeof fieldDef.onSet === 'function') {
			const patch = fieldDef.onSet(newRow, val);
			if (patch && typeof patch === 'object') {
				newRow = { ...newRow, ...patch };
			}
		}

		next[index] = newRow;
		onChange(next);
	};

	const triggerDisabledOptions = takenExclusivesExcluding(rows, index);

	const visibleFields = rowFields.filter(
		(f) => typeof f.when !== 'function' || f.when(rowData, activeBp)
	);

	return (
		<Box sx={{ border: 1, borderColor: 'divider', borderRadius: 1, overflow: 'hidden' }}>
			<Stack
				direction="row"
				alignItems="center"
				spacing={0.5}
				sx={{ px: 1, py: 0.5, bgcolor: 'action.hover', cursor: 'pointer' }}
				onClick={() => setOpen((o) => !o)}
			>
				<Typography variant="caption" sx={{ flex: 1, minWidth: 0, fontWeight: 600 }} noWrap>
					{rowSummary(rowData, rowFields)}
				</Typography>

				<Tooltip title="Play this interaction">
					<Box sx={{ position: 'relative', width: 24, height: 24, display: 'inline-flex' }}>
						{playing && (
							<Box
								component="svg"
								key={playKey}
								width="24"
								height="24"
								viewBox="0 0 24 24"
								sx={{ position: 'absolute', top: 0, left: 0, transform: 'rotate(-90deg)', pointerEvents: 'none' }}
							>
								{/* track */}
								<circle cx="12" cy="12" r="10.5" fill="none" stroke="currentColor" strokeOpacity="0.18" strokeWidth="2" />
								{/* sweeping arc — dashoffset animates full→0 */}
								<circle
									cx="12" cy="12" r="10.5" fill="none"
									stroke="currentColor" strokeWidth="2" strokeLinecap="round"
									strokeDasharray={RING_CIRCUMFERENCE}
									strokeDashoffset={RING_CIRCUMFERENCE}
									sx={{
										color: 'primary.main',
										animation: `aaePlayRing ${ringDurMs}ms linear forwards`,
									}}
								/>
							</Box>
						)}
						<IconButton
							size="small"
							onClick={handlePlayClick}
							aria-label="Play interaction"
							sx={{ width: 24, height: 24, color: playing ? 'primary.main' : undefined }}
						>
							<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
						</IconButton>
					</Box>
				</Tooltip>

				<Tooltip title="Remove interaction">
					<span>
						<IconButton
							size="small"
							onClick={(e) => { e.stopPropagation(); onRemove(index); }}
							aria-label="Remove interaction"
							sx={{ width: 24, height: 24 }}
						>
							×
						</IconButton>
					</span>
				</Tooltip>

				<IconButton size="small" sx={{ width: 24, height: 24 }} aria-label="Toggle">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
						style={{ transform: open ? 'rotate(180deg)' : 'none', transition: 'transform .15s' }}>
						<path d="M6 9l6 6 6-6" />
					</svg>
				</IconButton>
			</Stack>

			<Collapse in={open} unmountOnExit>
				<Box sx={{ p: 1.5 }}>
					{visibleFields.map((field) => (
						<RowField
							key={field.bind}
							field={field}
							rowData={rowData}
							activeBp={activeBp}
							onFieldChange={onFieldChange}
							triggerDisabledOptions={triggerDisabledOptions}
						/>
					))}
				</Box>
			</Collapse>
		</Box>
	);
}

/* ---------- the repeater ---------- */

export function InteractionsRepeaterInput({
	value,
	onChange,
	rowFields = [],
	rowDefaults = {},
	addLabel = 'Add Interaction',
	activeBp = null,
	onPlayRow,
}) {
	const rows = Array.isArray(value) ? value : [];

	// Stable per-row id so React keeps each card bound to its data across
	// edits. index-as-key would let a card's local state (open/closed) and
	// its props drift apart when rows are added/removed, which manifests as
	// "editing one row changes another". `_uid` is assigned lazily on first
	// render and persisted into the row object.
	const uidCounter = React.useRef(0);
	const keyForRow = (row) => {
		if (row && typeof row === 'object') {
			if (row._uid === undefined) {
				// eslint-disable-next-line no-param-reassign
				row._uid = `r${uidCounter.current++}`;
			}
			return row._uid;
		}
		return `r${uidCounter.current++}`;
	};

	const addRow = () => onChange([...rows, { ...rowDefaults }]);
	const removeAt = (i) => onChange(rows.filter((_, idx) => idx !== i));

	return (
		<Stack direction="column" spacing={1} sx={{ width: '100%' }}>
			{rows.map((row, index) => (
				<InteractionCard
					key={keyForRow(row)}
					rowData={row}
					rowFields={rowFields}
					index={index}
					activeBp={activeBp}
					rows={rows}
					onChange={onChange}
					onRemove={removeAt}
					onPlay={(i) => onPlayRow && onPlayRow(i)}
				/>
			))}

			{/* Bottom add button: small + right-aligned. Hidden when there are no
			    rows — the section header's "+" icon adds the first interaction. */}
			{rows.length > 0 && (
				<Box sx={{ display: 'flex', justifyContent: 'flex-end' }}>
					<IconButton
						size="small"
						color="primary"
						onClick={addRow}
						sx={{
							border: '1px dashed',
							borderRadius: 1,
							px: 1,
							py: 0.25,
							gap: 0.5,
						}}
					>
						<Typography variant="caption" sx={{ fontSize: '0.7rem' }}>+ {addLabel}</Typography>
					</IconButton>
				</Box>
			)}
		</Stack>
	);
}
