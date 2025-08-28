import MainLayout from "./MainLayout";
import "./index.css";

const bodyElement = document.body;
if (bodyElement) {
  const customDiv = document.createElement("div");
  customDiv.id = "wcf--custom-icon--toast";
  bodyElement.appendChild(customDiv);
}

document.body.classList.add("wcfcbi2025");
wp.element.render(
  <MainLayout />,
  document.getElementById("wcf--custom-icons-meta-box")
);

document.addEventListener("DOMContentLoaded", () => {
  const dId = document.getElementById("toplevel_page_wcf_addons_page");

  if (dId) {
    dId.classList.remove("wp-not-current-submenu");
    dId.classList.add("wp-has-current-submenu");
  }
});