/* eslint-env browser */

const gsap = window.gsap || { utils: { random: (min, max) => Math.random() * (max - min) + min } };

export const PREMIUM_EFFECTS = {
	"Origami Fold": { rotationX: (i) => i % 2 === 0 ? 90 : -90, opacity: 0, stagger: 0.05, duration: 1.2, ease: "expo.out", transformOrigin: "50% 50% -30px" },
	"Shutter Cascade": { rotationX: 90, opacity: 0, stagger: { each: 0.03, from: "start" }, duration: 0.8, ease: "power2.inOut", transformOrigin: "50% 0%" },
	"Typewriter 3D": { rotationY: -45, opacity: 0, stagger: 0.1, duration: 0.4, ease: "linear" },
	"Quantum Snap": { rotationY: () => gsap.utils.random(-180, 180), rotationX: () => gsap.utils.random(-90, 90), z: () => gsap.utils.random(400, 1000), opacity: 0, stagger: { amount: 0.8, from: "random" }, duration: 1.2, ease: "back.out(1.2)" },
	"Vortex Unwind": { rotationZ: 180, rotationY: 180, scale: 0.2, opacity: 0, stagger: 0.06, duration: 1.5, ease: "power3.out" },
	"Matrix Reveal": { rotationX: 180, scale: 0.5, opacity: 0, stagger: { from: "random", amount: 0.6 }, duration: 1.2, ease: "power4.out" },
	"Cyber Phantom": { rotationY: 180, rotationZ: -10, scale: 0.5, textShadow: "-30px 20px 0px rgba(0,255,255,0.5), 30px -20px 0px rgba(255,0,255,0.5)", opacity: 0, stagger: 0.05, duration: 1.5, ease: "back.out(1.5)" },
	"RB Curved Loop": { runAsTo: true, y: -25, rotationZ: (i, el, arr) => (i - arr.length/2) * 2, stagger: { each: 0.1, repeat: -1, yoyo: true }, duration: 0.8, ease: "sine.inOut" }
};

export const PREMIUM_EFFECT_OPTIONS = Object.keys(PREMIUM_EFFECTS).map((key) => {
	// e.g. "Cinematic Unfold" -> "premium_cinematic_unfold"
	const value = "premium_" + key.toLowerCase().replace(/[^a-z0-9]+/g, '_');
	return { value, label: '★ ' + key, _originalKey: key };
});
