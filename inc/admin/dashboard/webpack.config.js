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
    index: "./src/main.js",
    wizardSetup: "./src/wizardSetup.js",
    optout: "./src/opt-out.js",
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
      "@": path.resolve(__dirname, "src/"),
    },
  },
};
