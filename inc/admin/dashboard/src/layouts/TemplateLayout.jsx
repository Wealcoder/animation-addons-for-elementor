import TemplateHeader from "@/components/header/TemplateHeader";
import { Outlet } from "react-router-dom";

const TemplateLayout = () => {
  return (
    <>
      <TemplateHeader />
      <Outlet />
    </>
  );
};

export default TemplateLayout;
