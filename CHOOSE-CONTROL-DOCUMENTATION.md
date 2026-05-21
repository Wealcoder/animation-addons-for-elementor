# Documentation: Using the Custom `choose` Control Field

The custom `choose` control is a responsive UI input component designed for Elementor v4 atomic modules. Under the hood, it uses `@elementor/ui`'s `ToggleButtonGroup` and `ToggleButton` to render exclusive option buttons. Buttons can display either text labels or standard Elementor dashboard icons.

---

## 1. Declaring the Control in UI Configuration (`config.js`)

Add your control configuration object to the `fields` array of your module's `config.js` (e.g., `src/modules/atomic/extensions/sticky/config.js`):

```javascript
{
    bind: 'alignment', // Key suffix (maps to backend DB option 'aae_sticky_alignment')
    label: 'Alignment',
    control: 'choose',
    responsive: true, // Enables device-specific overrides (Desktop, Tablet, Mobile)
    defaultValue: 'center',
    options: [
        { 
            value: 'left', 
            label: 'Left', 
            icon: 'eicon-text-align-left' // CSS class from Elementor's core icon library
        },
        { 
            value: 'center', 
            label: 'Center', 
            icon: 'eicon-text-align-center' 
        },
        { 
            value: 'right', 
            label: 'Right', 
            icon: 'eicon-text-align-right' 
        },
    ],
    tab: 'Style', // The UI tab where this control is grouped ('Content' or 'Style')
}
```

### Option Properties:
* **`value`** (string | boolean, Required): The value saved to the settings model when this toggle option is selected.
* **`label`** or **`title`** (string, Optional): The tooltip label shown on hovering over the button. Also serves as fallback text if `icon` is not specified.
* **`icon`** (string, Optional): The CSS class name of the icon to display (e.g., `eicon-text-align-center`).

---

## 2. Registering the Schema on the PHP Backend (`Schema.php`)

Since this control is responsive, it saves values in a responsive JSON object wrapper (`aae-rj`). You must register it in the module's `Schema.php` file:

```php
// 1. Define the unique settings key constant (bindPrefix + bindName)
const STICKY_ALIGNMENT = 'aae_sticky_alignment';

// 2. Register it inside the schema builder method (e.g. add_sticky_props)
$schema[ self::STICKY_ALIGNMENT ] =
    Responsive_JSON_Prop_Type::make()
        ->default([
            'desktop' => 'center', // Default fallback value
        ])
        ->set_dependencies( $sticky_enabled_dependency ); // Apply conditional display dependency if needed
```

---

## 3. Emitting the Values in PHP Render Engine (`Render.php`)

To pass the responsive values down to the frontend HTML container via data attributes, use the responsive emission utility in `Render.php`:

```php
// Outputs responsive attributes:
// e.g. data-aae-sticky-alignment="center" data-aae-sticky-alignment-tablet="left" etc.
$this->emit_responsive( $settings, 'aae_sticky_alignment', 'alignment', 'center' );
```

---

## 4. Registering in the Editor-Bridge (`features.js`)

To keep the live Elementor editor preview in sync as options are toggled in real-time, declare the property mapping in `src/modules/atomic/editor-bridge/features.js`:

```javascript
const STICKY_RESPONSIVE = {
    // ... other properties
    aae_sticky_alignment: { configKey: 'alignment', default: 'center' },
};
```

---

## 5. Extracting and Using the Config in Frontend JS (`index.js`)

In the frontend JS runtime (e.g., `src/modules/atomic/effects/sticky/index.js`), read the resolved responsive value at the active device breakpoint:

```javascript
function readSticky(el) {
    const cfg = window.AAEADDON.read(el, STICKY_MAP);
    const r = window.AAEADDON.readResponsive;

    return {
        enabled:          r(cfg, 'enable', false),
        // ... other properties
        alignment:        r(cfg, 'alignment', 'center'), // Resolves active value with breakpoint fallbacks
    };
}
```

---

## 6. Under the Hood: React Component (`ChooseInput.jsx`)

The control is registered in the main inputs control registry mapping under `'choose'` in `ResponsiveRow.jsx`:

```javascript
import { ChooseInput } from "./inputs/ChooseInput";

const CONTROL_REGISTRY = {
  // ... other controls
  choose: { Component: ChooseInput, innerType: "string" },
};
```

The component itself resides in `src/modules/atomic/responsive-section/inputs/ChooseInput.jsx`:

```jsx
import * as React from 'react';
import { ToggleButtonGroup, ToggleButton } from '@elementor/ui';

export function ChooseInput({ value, onChange, disabled, options = [] }) {
	return (
		<ToggleButtonGroup
			value={value}
			exclusive
			onChange={(_, next) => {
				if (next !== null) {
					onChange(next);
				}
			}}
			disabled={disabled}
			size="small"
			sx={{ height: 32 }}
		>
			{options.map((opt) => (
				<ToggleButton
					key={opt.value}
					value={opt.value}
					title={opt.label || opt.title || opt.value}
					sx={{ px: 1.5, py: 0.5, minWidth: 36 }}
				>
					{opt.icon ? (
						<span className={opt.icon} style={{ fontSize: '14px' }} />
					) : (
						opt.label || opt.value
					)}
				</ToggleButton>
			))}
		</ToggleButtonGroup>
	);
}
```
