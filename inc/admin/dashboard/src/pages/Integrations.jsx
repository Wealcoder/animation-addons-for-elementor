import IntegrationTopBar from "@/components/integrations/IntegrationTopBar";
import ShowIntegrations from "@/components/integrations/ShowIntegrations";

const Integrations = () => {
  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <IntegrationTopBar />
      </div>
      <div className="mt-4">
        <ShowIntegrations />
      </div>
    </div>
  );
};

export default Integrations;
