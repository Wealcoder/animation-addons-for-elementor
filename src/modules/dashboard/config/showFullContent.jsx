import MainLayout from "@/layouts/MainLayout";
import CompleteImport from "@/pages/CompleteImport";
import Dashboard from "@/pages/Dashboard";
import DemoImporting from "@/pages/DemoImporting";
import Extensions from "@/pages/Extensions";
import FailImport from "@/pages/FailImport";
import FreePro from "@/pages/FreePro";
import Integrations from "@/pages/Integrations";
import RequiredFeatures from "@/pages/RequiredFeatures";
import StaterTemplate from "@/pages/StaterTemplate";
import Widgets from "@/pages/Widgets";

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
    case "demo-importing":
      return (
        <MainLayout.ThirdLayout>
          <DemoImporting />
        </MainLayout.ThirdLayout>
      );
    case "complete-import":
      return (
        <MainLayout.ThirdLayout>
          <CompleteImport />
        </MainLayout.ThirdLayout>
      );
    case "fail-import":
      return (
        <MainLayout.ThirdLayout>
          <FailImport />
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
