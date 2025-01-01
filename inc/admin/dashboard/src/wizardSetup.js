import { AppContextProvider } from "./context/app.context";
import WizardLayout from "./layouts/WizardLayout";
import "./index.css";

document.querySelector("body").classList.add("wcf-anim2024");
wp.element.render(
  <AppContextProvider>
    <WizardLayout />,
  </AppContextProvider>,
  document.getElementById("wcf-animation-addon-wizard")
);
