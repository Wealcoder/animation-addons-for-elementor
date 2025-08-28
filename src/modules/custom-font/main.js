import CustomFont from "./CustomFont";
import GlobalSettings from "./GlobalSettings";
import "./index.css";

wp.element.render(
  <CustomFont />,
  document.getElementById("wcf--custom-fonts-meta-box")
);

if (document.getElementById("wcf--custom-fonts-meta-box-side-setting")) {
  wp.element.render(
    <GlobalSettings />,
    document.getElementById("wcf--custom-fonts-meta-box-side-setting")
  );
}


document.addEventListener("DOMContentLoaded", () => {
  const dId = document.getElementById("toplevel_page_wcf_addons_page");

  if (dId) {
    dId.classList.remove("wp-not-current-submenu");
    dId.classList.add("wp-has-current-submenu");
  }
});