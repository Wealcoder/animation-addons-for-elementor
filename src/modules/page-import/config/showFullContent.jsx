import { lazy } from "react";
import MainLayout from "C/layouts/MainLayout";
import CompleteImport from "C/pages/CompleteImport";
import FailImport from "C/pages/FailImport";

const StaterTemplate = lazy(() => import("C/pages/StaterTemplate"));
const RequiredFeatures = lazy(() => import("C/pages/RequiredFeatures"));
const DemoImporting = lazy(() => import("C/pages/DemoImporting"));

export const ShowContent = (item) => {
  switch (item.tabKey) {
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
        <MainLayout.SecondLayout>
          <StaterTemplate />
        </MainLayout.SecondLayout>
      );
  }
};
