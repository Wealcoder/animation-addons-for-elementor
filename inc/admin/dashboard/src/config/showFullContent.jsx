import { lazy } from "react";
import MainLayout from "@/layouts/MainLayout";

const Dashboard = lazy(() => import("@/pages/Dashboard"));
const Extensions = lazy(() => import("@/pages/Extensions"));
const FreePro = lazy(() => import("@/pages/FreePro"));
const Widgets = lazy(() => import("@/pages/Widgets"));
const Integrations = lazy(() => import("@/pages/Integrations"));
const StaterTemplate = lazy(() => import("@/pages/StaterTemplate"));
const RequiredFeatures = lazy(() => import("@/pages/RequiredFeatures"));

export const ShowContent = (item) => {
  switch (item.tabKey) {
    case "dashboard":
      return (
        <MainLayout.FirstLayout>
          <Dashboard />
        </MainLayout.FirstLayout>
      );
    case "widgets":
      return (
        <MainLayout.FirstLayout>
          <Widgets />
        </MainLayout.FirstLayout>
      );
    case "extensions":
      return (
        <MainLayout.FirstLayout>
          <Extensions />
        </MainLayout.FirstLayout>
      );
    case "free-pro":
      return (
        <MainLayout.FirstLayout>
          <FreePro />
        </MainLayout.FirstLayout>
      );
    case "integrations":
      return (
        <MainLayout.FirstLayout>
          <Integrations />
        </MainLayout.FirstLayout>
      );
    case "stater-template":
      return (
        <MainLayout.SecondLayout>
          <StaterTemplate />
        </MainLayout.SecondLayout>
      );
    case "required-features":
      return (
        <MainLayout.ThirdLayout>
          <RequiredFeatures />
        </MainLayout.ThirdLayout>
      );
    default:
      return (
        <MainLayout.FirstLayout>
          <Dashboard />
        </MainLayout.FirstLayout>
      );
  }
};
