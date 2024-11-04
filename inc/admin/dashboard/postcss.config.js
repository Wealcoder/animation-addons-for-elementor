const tailwindAdmin = require("./tailwind.admin.config.js");
const tailwindFrontend = require("./tailwind.frontend.config.js");
// import tailwindFrontend from "./tailwind.frontend.config.mjs";

module.exports = {
  plugins: {
    "postcss-nested": {},
    tailwindcss: { tailwindAdmin },
    tailwindcss: { tailwindFrontend },
    autoprefixer: {},
  },
};
