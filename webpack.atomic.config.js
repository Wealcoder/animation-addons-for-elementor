const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");
const fs = require('fs');

const getAtomicWidgetEntries = () => {
  const entries = {};
  const widgetsPath = path.resolve(__dirname, "inc/AtomicWidgets/Widgets");
  if (fs.existsSync(widgetsPath)) {
    const widgets = fs.readdirSync(widgetsPath);
    widgets.forEach((widget) => {
      const jsDir = path.join(widgetsPath, widget, "assets/js");
      if (fs.existsSync(jsDir)) {
        const jsFiles = fs.readdirSync(jsDir).filter((file) => file.endsWith(".js"));
        jsFiles.forEach((file) => {
          const basename = path.basename(file, ".js");
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
  entry: getAtomicWidgetEntries(),
  output: {
    path: path.resolve(__dirname, "assets/build"),
    filename: "[name].js",
  },
};
