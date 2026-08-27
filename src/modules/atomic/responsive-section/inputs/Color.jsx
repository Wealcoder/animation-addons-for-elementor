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
}));

export function ColorInput({ value, onChange, disabled, placeholder }) {
  const [color, setColor] = useState(value ?? '#000000');
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
    setColor(value ?? '#000000');
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
                style={{ color: color || '#fff' }}
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
            color={color} 
            onChange={handlePickerChange} 
          />
          <Box sx={{ mt: 1, p: 1, backgroundColor: color, borderRadius: 1, height: '30px', border: '1px solid rgba(0, 0, 0, 0.15)' }} />
        </Box>
      </Popover>
    </>
  );
}