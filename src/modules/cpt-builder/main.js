import { AppContextProvider } from "./context/app.context";
import MainLayout from "./layouts/MainLayout";

import "./index.css";
const bodyElement = document.body;
if (bodyElement) {
  const customDiv = document.createElement("div");
  customDiv.id = "wcf--cpt-builder--toast";
  bodyElement.appendChild(customDiv);
}

document.body.classList.add("wcfcb2025");
wp.element.render(
  <AppContextProvider>
    <MainLayout />
  </AppContextProvider>,
  document.getElementById("wcf-cpt-builder")
);
