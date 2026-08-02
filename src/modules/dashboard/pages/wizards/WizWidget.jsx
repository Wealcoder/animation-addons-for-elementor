import ShowWizWidgets from "@/components/wizards/ShowWizWidgets";
import ShowWizAtomicWidgets from "@/components/wizards/ShowWizAtomicWidgets";
import WidgetTopBg from "../../../../../public/images/wizard/widget-top-bg.png";
import { useAtomicWidgets } from "@/hooks/app.hooks";

const WizWidget = () => {
  const { allAtomicWidgets } = useAtomicWidgets();

  // V4 only — a new install should never be offered the v3 widgets. The v3
  // list stays as a FALLBACK rather than being deleted, because the atomic
  // payload only exists when Atomic::meets_requirements() passes (Elementor
  // 4.x with the atomic experiment on). Without this branch, a user on an
  // older Elementor would reach an empty step and finish the wizard with
  // nothing offered at all.
  const hasAtomic = Object.keys(allAtomicWidgets?.elements || {}).length > 0;

  /*
   * Lead capture used to live here, POSTing straight to FluentCRM with an HTTP
   * Basic Auth username and password written into this component — which
   * webpack published to assets/build/9479.js, fetchable by anyone from any
   * site running the plugin. Never put a credential in a component.
   *
   * It now belongs to the consent checkbox on the Terms step
   * (WizardTerms.jsx), which is where the user actually agrees to share their
   * data, and runs through aae_wizard_subscribe server-side so no key ships at
   * all. Nothing to do on this step.
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
            {hasAtomic ? <ShowWizAtomicWidgets /> : <ShowWizWidgets />}
          </div>
        </div>
      </div>
    </div>
  );
};

export default WizWidget;
