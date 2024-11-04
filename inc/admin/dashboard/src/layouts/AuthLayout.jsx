import AuthHeader from "@/components/header/AuthHeader";

const AuthLayout = ({ children }) => {
  return (
    <div className="bg-background-secondary">
      <AuthHeader />
      <div className="min-h-[calc(100vh-82px)] flex justify-center items-center h-full">
        {children}
      </div>
    </div>
  );
};

export default AuthLayout;
