import { Toaster } from "./components/ui/sonner";
import { AppContextProvider } from "./context/app.context";
import "./index.css";
import MainLayout from "./layouts/MainLayout";

wp.element.render(
  <AppContextProvider>
    <MainLayout />
  </AppContextProvider>,
  document.getElementById("wcf-admin-ds-cr-js")
);

wp.element.render(<Toaster />, document.getElementById("wcf-admin-toast"));