import { useState, useEffect, useRef } from 'react';
import { TextField, Popover, InputAdornment, Box, styled } from '@elementor/ui';
import { HexColorPicker } from 'react-colorful';

const ColorIndicator = styled(Box)(({ theme }) => ({
  width: '32px',
  height: '32px',
  borderRadius: '4px',
  border: `1px solid ${theme.palette.divider || 'rgba(0, 0, 0, 0.15)'}`,
  cursor: 'pointer',
  backgroundColor: 'currentColor',
  '&:hover': {
    opacity: 0.8,
  },
  // An unset row gets the standard transparency checkerboard, so "no colour" is
  // visibly different from both white and black.
  '&.aae-color-unset': {
    backgroundImage:
      'linear-gradient(45deg, rgba(128,128,128,.35) 25%, transparent 25%),' +
      'linear-gradient(-45deg, rgba(128,128,128,.35) 25%, transparent 25%),' +
      'linear-gradient(45deg, transparent 75%, rgba(128,128,128,.35) 75%),' +
      'linear-gradient(-45deg, transparent 75%, rgba(128,128,128,.35) 75%)',
    backgroundSize: '8px 8px',
    backgroundPosition: '0 0, 0 4px, 4px -4px, -4px 0px',
  },
}));

/* Seed for the picker when the field is empty. Never written to the model on
   its own — only a real interaction does that. */
const PICKER_FALLBACK = '#000000';

export function ColorInput({ value, onChange, disabled, placeholder }) {
  // Empty stays EMPTY rather than falling back to a colour.
  //
  // This used to seed '#000000', which reads as "black is set" on every row the
  // builder has not touched — and on the WP Menu that is most of them, where an
  // unset colour means "inherit the menu text colour" or "transparent", not
  // black. The placeholder is the honest answer, and it can only show while the
  // field is genuinely empty. Callers that DO want a colour up front pass one as
  // `defaultValue`, so nothing they rely on changes.
  const [color, setColor] = useState(value ?? '');
  const [anchorEl, setAnchorEl] = useState(null);
  const indicatorRef = useRef(null);
  const isOpen = Boolean(anchorEl);

  // While the picker is open, our own `onChange` writes flow through
  // Elementor's settings store (runCommandSync) and echo back down as this
  // `value` prop. That echo doesn't land in the same tick as the drag, so
  // re-syncing local state from it on every render fights the user's own
  // pointer movement — react-colorful keeps getting fed a slightly-lagged
  // color mid-drag, which reads as the swatch/pointer constantly jittering.
  // Local state is authoritative while open; only resync from the external
  // value when the picker isn't actively being dragged.
  useEffect(() => {
    if (isOpen) return;
    setColor(value ?? '');
  }, [value, isOpen]);

  const handleTextFieldChange = (event) => {
    const newColor = event.target.value;
    setColor(newColor);
    onChange(newColor);
  };

  const handlePickerChange = (newColor) => {  
    setColor(newColor);
    onChange(newColor);
  };

  const handleOpenPicker = () => {
    // Deliberately does NOT write PICKER_FALLBACK into the model. Opening a
    // picker is not a decision; only dragging it (handlePickerChange) or typing
    // (handleTextFieldChange) is — so an empty row that is opened and then
    // dismissed stays empty and keeps inheriting.
    setAnchorEl(indicatorRef.current);
  };

  const handleClosePicker = () => {
    setAnchorEl(null);
  };

  return (
    <>
      <TextField
        value={color}
        onChange={handleTextFieldChange}
        disabled={disabled}
        placeholder={placeholder || '#000000'}
        size="small"
        fullWidth
        InputProps={{
          startAdornment: (
            <InputAdornment position="start">
              <ColorIndicator
                ref={indicatorRef}
                onClick={handleOpenPicker}
                style={{ color: color || 'transparent' }}
                className={color ? undefined : 'aae-color-unset'}
              />
            </InputAdornment>
          ),
        }}
      />

      <Popover
        open={Boolean(anchorEl)}
        anchorEl={anchorEl}
        onClose={handleClosePicker}
        anchorOrigin={{
          vertical: 'bottom',
          horizontal: 'left',
        }}
        transformOrigin={{
          vertical: 'top',
          horizontal: 'left',
        }}
      >
        <Box sx={{ p: 2 }}>
          <HexColorPicker 
            color={color || PICKER_FALLBACK} 
            onChange={handlePickerChange} 
          />
          <Box sx={{ mt: 1, p: 1, backgroundColor: color || PICKER_FALLBACK, borderRadius: 1, height: '30px', border: '1px solid rgba(0, 0, 0, 0.15)' }} />
        </Box>
      </Popover>
    </>
  );
}