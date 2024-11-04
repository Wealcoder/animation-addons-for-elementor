import Dashboard from "./pages/Dashboard";
import "./index.css";
import MainLayout from "./layouts/MainLayout";

wp.element.render(
  <MainLayout>
    <Dashboard />
  </MainLayout>,
  document.getElementById("wcf-admin-ds-cr-js")
);
