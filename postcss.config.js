const tailwindDashboard = require("./tailwind.dashboard.config.js");
const tailwindPageImport = require("./tailwind.pageImport.config.js");

module.exports = {
  plugins: {
    "postcss-nested": {},
    tailwindcss: { tailwindDashboard },
    tailwindcss: { tailwindPageImport },
    autoprefixer: {},
  },
};
