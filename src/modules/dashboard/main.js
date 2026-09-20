import { Toaster } from "./components/ui/sonner";
import { AppContextProvider } from "./context/app.context";
import "./index.css";
import MainLayout from "./layouts/MainLayout";
import AppErrorBoundary from "./components/shared/AppErrorBoundary";

document.addEventListener("DOMContentLoaded", function () {
   // Your code to run after the DOM is fully loaded 
   

   
wp.element.render(
  <AppErrorBoundary>
    <AppContextProvider>
      <MainLayout />
    </AppContextProvider>
  </AppErrorBoundary>,
  document.getElementById("wcf-admin-ds-cr-js")
);

wp.element.render(<Toaster />, document.getElementById("wcf-admin-toast"));


}); 