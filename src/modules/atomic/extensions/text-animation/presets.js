/* eslint-env browser */

const gsap = window.gsap || { utils: { random: (min, max) => Math.random() * (max - min) + min } };

export const PREMIUM_EFFECTS = {
	// --- CATEGORY 1: FLIPS & FOLDS (Classic 3D) ---
	"Cinematic Unfold": { rotationX: -110, z: -800, opacity: 0, stagger: 0.04, duration: 1.5, ease: "power4.out" },
	"Origami Fold": { rotationX: (i) => i % 2 === 0 ? 90 : -90, opacity: 0, stagger: 0.05, duration: 1.2, ease: "expo.out", transformOrigin: "50% 50% -30px" },
	"Shutter Cascade": { rotationX: 90, opacity: 0, stagger: { each: 0.03, from: "start" }, duration: 0.8, ease: "power2.inOut", transformOrigin: "50% 0%" },
	"Domino Fall": { rotationX: 90, z: -200, opacity: 0, stagger: 0.05, duration: 1, ease: "bounce.out", transformOrigin: "50% 100%" },
	"Coin Flip Double": { rotationY: 720, opacity: 0, stagger: 0.03, duration: 1.4, ease: "power3.inOut" },
	"Carousel Spin": { rotationY: 90, x: 200, opacity: 0, stagger: 0.06, duration: 1.2, ease: "back.out(1.2)", transformOrigin: "0% 50%" },
	"Typewriter 3D": { rotationY: -45, opacity: 0, stagger: 0.1, duration: 0.4, ease: "linear" },
	"Horizon Rise": { rotationX: -90, y: 100, opacity: 0, stagger: 0.04, duration: 1.2, ease: "back.out(1.5)", transformOrigin: "50% 100%" },

	// --- CATEGORY 2: DEPTH & SCALE (Z-Axis Heavy) ---
	"Magnetic Center": { z: 600, rotationY: 45, opacity: 0, stagger: { each: 0.05, from: "center" }, duration: 1.4, ease: "elastic.out(1, 0.7)" },
	"Abyss Emerge": { z: -2000, opacity: 0, stagger: 0.02, duration: 2, ease: "expo.out" },
	"Phantom Float": { y: 80, rotationX: -45, opacity: 0, stagger: 0.05, duration: 1.8, ease: "sine.out" },
	"Zoom Ripple": { scale: 0, z: -500, rotationX: 45, opacity: 0, stagger: { each: 0.05, from: "edges" }, duration: 1.2, ease: "back.out(1.7)" },
	"Echo Drop": { z: 1000, opacity: 0, stagger: { each: 0.04, from: "end" }, duration: 1.5, ease: "bounce.out" },

	// --- CATEGORY 3: CHAOS & DISTORTION (Advanced Math) ---
	"Quantum Snap": { rotationY: () => gsap.utils.random(-180, 180), rotationX: () => gsap.utils.random(-90, 90), z: () => gsap.utils.random(400, 1000), opacity: 0, stagger: { amount: 0.8, from: "random" }, duration: 1.2, ease: "back.out(1.2)" },
	"Vortex Unwind": { rotationZ: 180, rotationY: 180, scale: 0.2, opacity: 0, stagger: 0.06, duration: 1.5, ease: "power3.out" },
	"Gravity Smash": { y: -500, rotationZ: () => gsap.utils.random(-90, 90), opacity: 0, stagger: 0.03, duration: 1.2, ease: "bounce.out" },
	"Helix Twist": { rotationY: (i) => i * 45, y: 100, opacity: 0, stagger: 0.02, duration: 1.5, ease: "power2.out" },
	"Matrix Reveal": { rotationX: 180, scale: 0.5, opacity: 0, stagger: { from: "random", amount: 0.6 }, duration: 1.2, ease: "power4.out" },
	"Elastic Pull": { z: -800, rotationY: -90, opacity: 0, stagger: 0.05, duration: 1.8, ease: "elastic.out(1.2, 0.4)" },

	// --- CATEGORY 4: ELEGANT & SMOOTH (Corporate) ---
	"Pendulum Swing": { rotationX: 120, opacity: 0, stagger: 0.04, duration: 2, ease: "elastic.out(1.2, 0.4)", transformOrigin: "50% -100%" },
	"Velvet Glide": { y: 50, z: -100, rotationX: -30, opacity: 0, stagger: 0.03, duration: 1.5, ease: "power3.inOut" },

	// --- CATEGORY 5: DUPLICATE ECHO ---
	"Clone Spin 3D": { rotationX: -360, z: -400, textShadow: "0px 60px 0px rgba(255,69,0,0.3), 0px -60px 0px rgba(255,255,255,0.1)", opacity: 0, stagger: 0.04, duration: 1.6, ease: "power3.inOut" },
	"Perspective Split": { rotationY: 360, scale: 1.2, textShadow: "40px 0px 0px rgba(255,69,0,0.4), -40px 0px 0px rgba(255,255,255,0.2)", opacity: 0, stagger: { each: 0.05, from: "center" }, duration: 1.8, ease: "back.out(1.5)" },
	"Cyber Phantom": { rotationY: 180, rotationZ: -10, scale: 0.5, textShadow: "-30px 20px 0px rgba(0,255,255,0.5), 30px -20px 0px rgba(255,0,255,0.5)", opacity: 0, stagger: 0.05, duration: 1.5, ease: "back.out(1.5)" },
	"Holo Stack 3D": { rotationX: 90, y: -80, textShadow: "0px 30px 0px rgba(255,69,0,0.4), 0px 60px 0px rgba(255,69,0,0.2), 0px 90px 0px rgba(255,69,0,0.1)", opacity: 0, stagger: { each: 0.05, from: "end" }, duration: 1.4, ease: "power3.inOut" },

	// --- CATEGORY 6: REACTBITS TEXT TYPE & BEHAVIORS ---
	"RB Classic Type": { display: "none", opacity: 0, stagger: 0.06, duration: 0.01 },
	"RB Human Type": { display: "none", opacity: 0, stagger: () => gsap.utils.random(0.02, 0.25), duration: 0.01 },
	"RB Backspace Loop": { display: "none", opacity: 0, stagger: { each: 0.08, yoyo: true, repeat: -1, repeatDelay: 1.5 }, duration: 0.01 },
	"RB Terminal 3D": { display: "none", opacity: 0, rotationX: 90, color: "var(--accent)", stagger: 0.06, duration: 0.3, ease: "back.out(2)" },
	"RB Curved Wave": { y: 40, rotationZ: (i) => (i % 2 === 0 ? 15 : -15), opacity: 0, stagger: 0.05, duration: 1.5, ease: "back.out(1.5)" },
	"RB Curved Loop": { runAsTo: true, y: -25, rotationZ: (i, el, arr) => (i - arr.length/2) * 2, stagger: { each: 0.1, repeat: -1, yoyo: true }, duration: 0.8, ease: "sine.inOut" },
	"RB Pressure Reveal": { scaleX: 0.2, scaleY: 2.5, letterSpacing: "-10px", opacity: 0, stagger: { from: "center", amount: 0.5 }, duration: 1.5, ease: "elastic.out(1, 0.3)" },
	"RB Pressure Loop": { runAsTo: true, scaleX: 0.6, scaleY: 1.3, color: "var(--accent)", stagger: { each: 0.08, repeat: -1, yoyo: true }, duration: 0.5, ease: "power2.inOut" }
};

export const PREMIUM_EFFECT_OPTIONS = Object.keys(PREMIUM_EFFECTS).map((key) => {
	// e.g. "Cinematic Unfold" -> "premium_cinematic_unfold"
	const value = "premium_" + key.toLowerCase().replace(/[^a-z0-9]+/g, '_');
	return { value, label: '★ ' + key, _originalKey: key };
});
