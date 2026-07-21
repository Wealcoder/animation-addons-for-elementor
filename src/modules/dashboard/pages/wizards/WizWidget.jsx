import ShowWizWidgets from "@/components/wizards/ShowWizWidgets";
import WidgetTopBg from "../../../../../public/images/wizard/widget-top-bg.png";
import { useSkip } from "@/hooks/app.hooks";
import { useEffect } from "react";

// Lead-capture relay endpoint. The plugin sends only lead data here; the
// relay server holds the private Brevo API key and forwards the contact to
// Brevo, so the key is never exposed in this bundle.
//
// Local testing (Animation Addons Lead Relay plugin on this site):
const LEADS_API_ENDPOINT = "http://animation.test/wp-json/leads/v1/subscribe";
// Production (swap to this once the relay is live on your domain):
// const LEADS_API_ENDPOINT = "https://api.animationaddons.com/v1/leads";

/**
 * Derive a display name from the local part of an email address.
 * Used as a fallback when the user has no first name on record.
 */
function extractNameFromEmail(email) {
  const nameParts = email.split("@")[0].split(".");
  return nameParts
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

/**
 * Build the lead payload from the current WordPress user.
 *
 * We send neutral lead fields; the relay API maps them onto Brevo
 * attributes (FIRSTNAME/LASTNAME/COMPANY/SMS) and the target list ID,
 * so no Brevo-specific config or credentials live in the plugin.
 */
function buildLeadPayload() {
  const user = WCF_ADDONS_ADMIN?.user ?? {};
  const firstName =
    user.f_name && user.f_name !== ""
      ? user.f_name
      : extractNameFromEmail(user.email);

  // Only include fields that actually have a value.
  const lead = { email: user.email };
  if (firstName) lead.firstName = firstName;
  if (user.l_name) lead.lastName = user.l_name;
  if (user.company) lead.company = user.company;
  if (user.phone) lead.phone = user.phone;

  // Lightweight source metadata to help attribute the lead server-side.
  lead.source = "animation-addon";
  lead.site = WCF_ADDONS_ADMIN?.home_url || WCF_ADDONS_ADMIN?.adminURL || "";

  return lead;
}

/**
 * Send the lead to our relay API, which subscribes it to Brevo.
 */
async function subscribeLead() {
  const response = await fetch(LEADS_API_ENDPOINT, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(buildLeadPayload()),
  });

  if (!response.ok) {
    throw new Error(`HTTP error! Status: ${response.status}`);
  }

  // Tolerate empty (204) responses from the relay API.
  if (response.status !== 204) {
    await response.json();
  }
}

const WizWidget = () => {
  const { isSkipTerms } = useSkip();

  // Subscribe the current user once, mirroring the previous
  // "run once per browser" behavior via the same localStorage flag.
  async function addSubscriber() {
    try {
      await subscribeLead();
    } catch (error) {
      // Swallow errors: subscription is best-effort and must not block
      // the wizard. We still set the flag so we don't retry on reload.
    } finally {
      localStorage.setItem("wcfanim_addon_subscribe", "yes");
    }
  }

  useEffect(() => {
    if (
      !isSkipTerms &&
      localStorage.getItem("wcfanim_addon_subscribe") != "yes"
    ) {
      addSubscriber();
    }
  }, []);
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
