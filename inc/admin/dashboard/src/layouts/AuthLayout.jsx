import AuthHeader from "@/components/header/AuthHeader";
import { Outlet } from "react-router-dom";

const AuthLayout = () => {
  return (
    <div className="bg-background-secondary">
      <AuthHeader />
      <div className="min-h-[calc(100vh-82px)] flex justify-center items-center h-full">
        <Outlet />
      </div>
    </div>
  );
};

export default AuthLayout;
