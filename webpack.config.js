const defaultConfig = require("@wordpress/scripts/config/webpack.config");
// Import the helper to find and generate the entry points in the src directory
const { getWebpackEntryPoints } = require("@wordpress/scripts/utils/config");
const path = require("path");

module.exports = {
  ...defaultConfig,
  externals: {
    react: "React",
    "react-dom": "ReactDOM",
  },
  entry: {
    ...getWebpackEntryPoints(),
    "modules/dashboard/index": "./src/modules/dashboard/main.js",
    "modules/dashboard/wizardSetup": "./src/modules/dashboard/wizardSetup.js",
    "modules/dashboard/opt-out": "./src/modules/dashboard/opt-out.js",
    "modules/page-import/index": "./src/modules/page-import/main.js",
    "modules/custom-font/main": "./src/modules/custom-font/main.js",
    "modules/custom-icon/main": "./src/modules/custom-icon/main.js",
    "modules/cpt-builder/main": "./src/modules/cpt-builder/main.js",
    "modules/nested-slider/editor/index":
      "./src/modules/nested-slider/editor/index.js",
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
