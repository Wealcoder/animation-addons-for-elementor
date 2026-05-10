export const PRESETS = {
	fadeIn:      { from: { opacity: 0 } },
	fadeInUp:    { from: { opacity: 0, y: 40 } },
	fadeInDown:  { from: { opacity: 0, y: -40 } },
	fadeInLeft:  { from: { opacity: 0, x: -40 } },
	fadeInRight: { from: { opacity: 0, x: 40 } },
	slideUp:     { from: { y: 80 } },
	slideDown:   { from: { y: -80 } },
	zoomIn:      { from: { opacity: 0, scale: 0.6 } },
	zoomOut:     { from: { opacity: 0, scale: 1.4 } },
	rotateIn:    { from: { opacity: 0, rotation: -180 } },
	flipInX:     { from: { opacity: 0, rotationX: 90 } },
	flipInY:     { from: { opacity: 0, rotationY: 90 } },
};

export const RESET_TO = {
	opacity: 1, x: 0, y: 0, scale: 1, rotation: 0, rotationX: 0, rotationY: 0,
};
