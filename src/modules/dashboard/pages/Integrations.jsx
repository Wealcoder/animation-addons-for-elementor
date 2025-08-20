import IntegrationTopBar from "@/components/integrations/IntegrationTopBar";
import ShowIntegrations from "@/components/integrations/ShowIntegrations";
import ShowIntegrationsLibrary from "@/components/integrations/ShowIntegrationsLibrary";

const Integrations = () => {
  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <IntegrationTopBar />
      </div>
      <div className="mt-4 space-y-11">
        <ShowIntegrations />

        <ShowIntegrationsLibrary />
      </div>
    </div>
  );
};

export default Integrations;
