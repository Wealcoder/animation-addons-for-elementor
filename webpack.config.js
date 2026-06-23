const defaultConfig = require("@wordpress/scripts/config/webpack.config");
// Import the helper to find and generate the entry points in the src directory
const { getWebpackEntryPoints } = require("@wordpress/scripts/utils/config");
const path = require("path");

const fs = require('fs');

// Helper to automatically find all JS files inside AtomicWidgets/Widgets/*/assets/js/
const getAtomicWidgetEntries = () => {
  const entries = {
    "../atomic/js/atomic-editor": "./inc/AtomicWidgets/assets/js/atomic-editor.js"
  };
  const widgetsPath = path.resolve(__dirname, "inc/AtomicWidgets/Widgets");
  if (fs.existsSync(widgetsPath)) {
    const widgets = fs.readdirSync(widgetsPath).filter((w) => !w.endsWith('-prev') && !w.endsWith('_prev'));
    widgets.forEach((widget) => {
      const jsDir = path.join(widgetsPath, widget, "assets/js");
      if (fs.existsSync(jsDir)) {
        const jsFiles = fs.readdirSync(jsDir).filter((file) => file.endsWith(".js"));
        jsFiles.forEach((file) => {
          const basename = path.basename(file, ".js");
          // Outputs to assets/atomic/js/[basename].js
          entries[`../atomic/js/${basename}`] = `./inc/AtomicWidgets/Widgets/${widget}/assets/js/${file}`;
        });
      }
    });
  }
  return entries;
};

module.exports = {
  ...defaultConfig,
  externals: {
    // Elementor V2 packages — provided by elementor-v2-* WordPress script handles
    // at runtime. Marking them external keeps our bundle small and ensures we
    // share the same instance Elementor's editor uses (so registry calls like
    // registerControlReplacement land in the registry the panel actually reads).
    "@elementor/editor-controls":   ["elementorV2", "editorControls"],
    "@elementor/editor-elements":   ["elementorV2", "editorElements"],
    "@elementor/editor-props":      ["elementorV2", "editorProps"],
    "@elementor/editor-responsive": ["elementorV2", "editorResponsive"],
    "@elementor/editor-ui":         ["elementorV2", "editorUi"],
    "@elementor/frontend-handlers": ["elementorV2", "frontendHandlers"],
    "@elementor/ui":                ["elementorV2", "ui"],
    react:                          "React",
    "react-dom":                    "ReactDOM",
  },
  entry: {
    ...getWebpackEntryPoints(),
    ...getAtomicWidgetEntries(),
    "modules/dashboard/index": "./src/modules/dashboard/main.js",
    "modules/dashboard/wizardSetup": "./src/modules/dashboard/wizardSetup.js",
    "modules/dashboard/opt-out": "./src/modules/dashboard/opt-out.js",
    "modules/page-import/index": "./src/modules/page-import/main.js",
    "modules/custom-font/main": "./src/modules/custom-font/main.js",
    "modules/custom-icon/main": "./src/modules/custom-icon/main.js",
    "modules/cpt-builder/main": "./src/modules/cpt-builder/main.js",
    "modules/nested-slider/editor/index":
      "./src/modules/nested-slider/editor/index.js",
    // Core runtime — always loaded (when any AAE effect is on the page).
    // Exposes window.AAERegistry that every effect bundle registers into.
    "modules/atomic/common": "./src/modules/atomic/common.js",
    "modules/atomic/editor-bridge": "./src/modules/atomic/editor-bridge.js",
    // Per-effect bundles. Each is loaded conditionally by Render.php only
    // when a widget on the page actually uses that effect.
    "modules/atomic/effects/animation": "./src/modules/atomic/effects/animation/index.js",
    "modules/atomic/effects/image-animation": "./src/modules/atomic/effects/image-animation/index.js",
    "modules/atomic/effects/image-hover": "./src/modules/atomic/effects/image-hover/index.js",
    "modules/atomic/effects/horizontal": "./src/modules/atomic/effects/horizontal-scroll-anim/index.js",
    "modules/atomic/effects/sticky": "./src/modules/atomic/effects/sticky/index.js",
    "modules/atomic/effects/mouse-move-effect": "./src/modules/atomic/effects/mouse-move-effect/index.js",
    "modules/atomic/effects/cursor-hover-effect": "./src/modules/atomic/effects/cursor-hover-effect/index.js",
    "modules/atomic/effects/advance-tooltip": "./src/modules/atomic/effects/advance-tooltip/index.js",
    "modules/atomic/effects/tilt": "./src/modules/atomic/effects/tilt/index.js",
    "modules/atomic/effects/wrapper-link": "./src/modules/atomic/effects/wrapper-link/index.js",
    "modules/atomic/effects/scroll-to": "./src/modules/atomic/effects/scroll-to/index.js",
    "modules/atomic/effects/parallax": "./src/modules/atomic/effects/parallax/index.js",
    "modules/atomic/effects/custom-css": "./src/modules/atomic/effects/custom-css/index.js",
  },
  output: {
    path: path.resolve(__dirname, "assets/build"), // Custom output directory
    filename: "[name].js", // Output bundle filename
    // publicPath: "/assets/", // Public URL of the output directory when referenced in a browser
  },
  module: {
    ...defaultConfig.module,
    rules: [
      ...defaultConfig.module.rules,
      // Additional rules can be added here
    ],
  },
  plugins: [
    ...defaultConfig.plugins,
    // Additional plugins can be added here
  ],
  resolve: {
    extensions: [".js", ".jsx"],
    alias: {
      "@": path.resolve(__dirname, "src/modules/dashboard"),
      C: path.resolve(__dirname, "src/modules/page-import"),
      S: path.resolve(__dirname, "src/modules/cpt-builder/"),
    },
  },
};
