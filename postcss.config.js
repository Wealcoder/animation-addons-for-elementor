const tailwindDashboard = require("./tailwind.dashboard.config.js");

module.exports = {
  plugins: {
    "postcss-nested": {},
    tailwindcss: { tailwindDashboard },
    autoprefixer: {},
  },
};
