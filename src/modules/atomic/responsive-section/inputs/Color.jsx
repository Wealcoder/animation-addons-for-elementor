import { useState, useEffect, useRef } from 'react';
import { TextField, Popover, InputAdornment, Box, styled } from '@elementor/ui';
import { HexColorPicker } from 'react-colorful';

const ColorIndicator = styled(Box)(({ theme }) => ({
  width: '32px',
  height: '32px',
  borderRadius: '4px',
  border: `2px solid ${theme.palette.divider}`,
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

  useEffect(() => {
    setColor(value ?? '#000000');
  }, [value]);

  const handleTextFieldChange = (event) => {
    const newColor = event.target.value;
    setColor(newColor);
    onChange(newColor);
  };

  const handlePickerChange = (newColor) => {
    console.log('Picker changed:', newColor);
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
          <Box sx={{ mt: 1, p: 1, backgroundColor: color, borderRadius: 1, height: '30px' }} />
        </Box>
      </Popover>
    </>
  );
}