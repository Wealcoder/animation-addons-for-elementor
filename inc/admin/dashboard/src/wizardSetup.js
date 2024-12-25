import { AppContextProvider } from "./context/app.context";
import "./index.css";
import WizardLayout from "./layouts/WizardLayout";

document.getElementById("wpbody").classList.add("wcf-anim2024");
wp.element.render(
  <AppContextProvider>
    <WizardLayout />,
  </AppContextProvider>,
  document.getElementById("wcf-animation-addon-wizard")
);
