import AuthLayout from "@/layouts/AuthLayout";
import MainLayout from "@/layouts/MainLayout";
import TemplateLayout from "@/layouts/TemplateLayout";
import CompleteImport from "@/pages/CompleteImport";
import Dashboard from "@/pages/Dashboard";
import DemoImporting from "@/pages/DemoImporting";
import Extensions from "@/pages/Extensions";
import ForgotPassword from "@/pages/ForgotPassword";
import FreePro from "@/pages/FreePro";
import Login from "@/pages/Login";
import Registration from "@/pages/registration";
import RequiredFeatures from "@/pages/RequiredFeatures";
import StaterTemplate from "@/pages/StaterTemplate";
import Widgets from "@/pages/Widgets";
import { createBrowserRouter } from "react-router-dom";

const routes = createBrowserRouter([
  {
    path: "/wp-admin",
    element: <MainLayout />,
    children: [
      { 
        path: "/admin.php?page=wcf_addons_settings",
        element: <Dashboard />,
      },
      {
        path: "/widgets",
        element: <Widgets />,
      },
      {
        path: "/extensions",
        element: <Extensions />,
      },
      {
        path: "/free-pro",
        element: <FreePro />,
      },
    ],
  },
  {
    path: "/",
    element: <TemplateLayout />,
    children: [
      {
        path: "/stater-template",
        element: <StaterTemplate />,
      },
    ],
  },
  {
    path: "/",
    element: <AuthLayout />,
    children: [
      {
        path: "/registration",
        element: <Registration />,
      },
      {
        path: "/login",
        element: <Login />,
      },
      {
        path: "/forgot-password",
        element: <ForgotPassword />,
      },
      {
        path: "/required-features",
        element: <RequiredFeatures />,
      },
      {
        path: "/demo-importing",
        element: <DemoImporting />,
      },
      {
        path: "/complete-import",
        element: <CompleteImport />,
      },
    ],
  },
]);

export default routes;
