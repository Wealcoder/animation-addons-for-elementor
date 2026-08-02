import ShowWizWidgets from "@/components/wizards/ShowWizWidgets";
import WidgetTopBg from "../../../../../public/images/wizard/widget-top-bg.png";

const WizWidget = () => {
  /*
   * Lead capture used to run here, POSTing to a FluentCRM instance with an
   * HTTP Basic Auth username and password written into this component — which
   * webpack published to assets/build/9479.js, fetchable by anyone from any
   * site running the plugin. Never put a credential in a component.
   *
   * It now belongs to the consent checkbox on the Terms step
   * (WizardTerms.jsx) and runs server-side through aae_wizard_subscribe, so no
   * key ships at all. Nothing to do on this step.
   */
  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="bg-[linear-gradient(0deg,rgba(245,246,248,0.50)_0%,rgba(245,246,248,0.50)_100%)] rounded-lg">
        <div
          className="min-h-[65vh] bg-no-repeat bg-contain pb-6"
          style={{ backgroundImage: `url(${WidgetTopBg})` }}
        >
          <div className="pt-[120px] max-w-[730px] mx-auto text-center flex flex-col gap-3">
            <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
              Activate Widgets You Want to Use
            </h1>
            <p className="text-lg text-text-secondary">
              Enhance your website's functionality by activating widgets that
              suit your needs.
            </p>
          </div>
          <div className="mt-[56px] max-w-[1184px] mx-auto border-[10px] border-white rounded-lg">
            <ShowWizWidgets />
          </div>
        </div>
      </div>
    </div>
  );
};

export default WizWidget;
