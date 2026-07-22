import TemplateTopBg from "../../../../../public/images/wizard/template-top-bg.png";
import Shape1 from "../../../../../public/images/wizard/shape1.png";
import Shape2 from "../../../../../public/images/wizard/shape2.png";
import Shape3 from "../../../../../public/images/wizard/shape3.png";
import Shape4 from "../../../../../public/images/wizard/shape4.png";
import Shape5 from "../../../../../public/images/wizard/shape5.png";
import Shape6 from "../../../../../public/images/wizard/shape6.png";
import CredentialAlert from "@/components/wizards/CredentialAlert";
import { Checkbox } from "@/components/ui/checkbox";
import { useSkip } from "@/hooks/app.hooks";
import { useState } from "react";

// Lead-capture relay endpoint. The plugin sends only lead data here; the
// relay server holds the private Brevo API key and forwards the contact to
// Brevo, so the key is never exposed in this bundle.
//
// Local testing (Animation Addons Lead Relay plugin on this site):
const LEADS_API_ENDPOINT = "https://bricksfly.com/wp-json/leads/v1/subscribe";
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

const WizardTerms = () => {
  const { isSkipTerms, setIsSkipTerms } = useSkip();
  const [hasSubscribed, setHasSubscribed] = useState(false);

  const handleCheckboxChange = async () => {
    const newValue = !isSkipTerms;
    const isChecking = !newValue; // Checked = accepted
    setIsSkipTerms(newValue);

    if (isChecking && !hasSubscribed && localStorage.getItem("wcfanim_addon_subscribe") !== "yes") {
      const payload = buildLeadPayload();

      try {
        const response = await fetch(LEADS_API_ENDPOINT, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(payload),
        });

        if (!response.ok) {
          console.error("❌ API failed:", response.status);
          throw new Error(`HTTP error! Status: ${response.status}`);
        }

        if (response.status !== 204) {
          console.log("✅ API response:", await response.json());
        } else {
          console.log("✅ API returned 204 (updated)");
        }

        localStorage.setItem("wcfanim_addon_subscribe", "yes");
      } catch (error) {
        console.error("❌ Subscription error:", error);
      } finally {
        setHasSubscribed(true);
      }
    }
  };

  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="bg-[linear-gradient(0deg,rgba(245,246,248,0.50)_0%,rgba(245,246,248,0.50)_100%)] rounded-lg relative">
        <div
          className="min-h-[75vh] bg-no-repeat bg-container pb-6 "
          style={{ backgroundImage: `url(${TemplateTopBg})` }}
        >
          <div className="pt-[80px] max-w-[730px] mx-auto text-center flex flex-col gap-3">
            <div className="bg-white rounded-full py-[5px] ps-2 pe-2.5 mx-auto max-w-[180px] flex justify-center items-center gap-1.5 shadow-[0px_0px_0px_1px_rgba(44,64,94,0.06),0px_1px_1px_0px_rgba(44,64,94,0.04),0px_2px_4px_0px_rgba(44,64,94,0.08)]">
              <span className="flex justify-center items-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 16 16"
                  fill="none"
                >
                  <g clip-path="url(#clip0_2780_1024)">
                    <path
                      d="M7.07627 11.8641L7.66133 10.5239C8.18207 9.33139 9.11926 8.38206 10.2883 7.86313L11.8988 7.14826C12.4108 6.92099 12.4108 6.17611 11.8988 5.94884L10.3386 5.25629C9.13946 4.72401 8.18546 4.73953 7.67367 2.50629L7.081 1.07815C6.86106 0.548169 6.12879 0.548171 5.90887 1.07815L5.31618 2.50627C4.80438 3.73953 3.85035 4.72401 2.65123 5.25629L1.09105 5.94884C0.579024 6.17611 0.579024 6.92099 1.09105 7.14826L2.70153 7.86313C3.87059 8.38206 4.80781 9.33139 5.3285 10.5239L5.9136 11.8641C6.13851 12.3791 6.85133 12.3791 7.07627 11.8641ZM12.9343 15.1269L13.0988 14.7498C13.3921 14.0774 13.9205 13.542 14.5797 13.2491L15.0866 13.0239C15.3608 12.9021 15.3608 12.5036 15.0866 12.3818L14.6081 12.1691C13.9319 11.8687 13.3941 11.3135 13.1057 10.6183L12.9368 10.2107C12.819 9.92673 12.4263 9.92673 12.3085 10.2107L12.1396 10.6183C11.8513 11.3135 11.3135 11.3135 10.6373 12.1691L10.1587 12.3818C9.8846 12.5036 9.8846 12.5036 10.1587 12.3818L10.6657 13.2491C11.3249 13.542 11.8532 14.0774 12.1465 14.7498L12.3111 15.1269C12.4315 15.403 12.8138 15.403 12.9343 15.1269Z"
                      fill="#FC6848"
                    />
                  </g>
                  <defs>
                    <clipPath id="clip0_2780_1024">
                      <rect width="16" height="16" fill="white" />
                    </clipPath>
                  </defs>
                </svg>
              </span>
              <p className="text-sm font-medium">
                Version {WCF_ADDONS_ADMIN?.version}
              </p>
            </div>
            <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
              Get Started with with AAE Animation Addons
            </h1>
          </div>
          <div className="mt-[40px] w-[600px] rounded-3xl mx-auto border-[10px] border-white overflow-hidden">
            <div className="bg-[linear-gradient(180deg,#F0F4F8_0%,#FEF3EC_100%)] p-6">
              <p className="text-base text-text-secondary text-center mt-[7px] mb-6">
                Thank you for choosing Animation Addons for Elementor. Follow
                these simple steps of easy setup wizard & enjoy your Elementor
                web-building experience now!
              </p>
            </div>
          </div>
          <div className="mt-[40px] w-[600px] mx-auto text-center">
            <div className="flex justify-center items-center gap-2.5 ps-3 pe-4 pt-[11px] pb-3 rounded-[10px]">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="aae-plugin-continuing-terms"
                  checked={!isSkipTerms}
                  onCheckedChange={handleCheckboxChange}
                />
                <label
                  htmlFor="aae-plugin-continuing-terms"
                  className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                >
                  By continuing, you allow this plugin to collect your data.
                </label>
              </div>
              <CredentialAlert />
            </div>
          </div>
        </div>

        {/* shapes  */}
        <div className="hidden xl:block absolute top-[114px] left-[69px] w-[54px] h-[54px]">
          <img
            src={Shape1}
            alt="Shape"
            className="w-full h-full -rotate-[15deg] rounded-[14.4px] shadow-[3.36px_6.72px_16.8px_0px_rgba(17,22,49,0.04),0px_44.52px_26.88px_0px_rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[310px] left-[137px] w-[56px] h-[56px]">
          <img
            src={Shape2}
            alt="Shape"
            className="w-full h-full -rotate-[15deg] rounded-[14px] shadow-[3.349px_6.698px_16.745px_0px rgba(17,22,49,0.04),0px_44.374px_26.792px_0px rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[432px] left-[30px] w-[52px] h-[52px]">
          <img
            src={Shape3}
            alt="Shape"
            className="w-full h-full -rotate-[15deg] rounded-[13.87px]"
            style={{
              filter:
                "drop-shadow(0px 36.747px 22.187px rgba(0, 0, 0, 0.02)) drop-shadow(2.773px 5.547px 13.867px rgba(17, 22, 49, 0.04))",
            }}
          />
        </div>
        <div className="hidden xl:block absolute top-[106px] right-[66px] w-[60px] h-[60px]">
          <img
            src={Shape4}
            alt="Shape"
            className="w-full h-full rotate-[15deg] rounded-[14.04px] shadow-[3.2px_6.4px_16px_0px rgba(17,22,49,0.04),0px_42.4px_25.6px_0px rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[313px] right-[149px] w-[52px] h-[52px]">
          <img
            src={Shape5}
            alt="Shape"
            className="w-full h-full rotate-[15deg] rounded-[14.16px] shadow-[2.831px_5.662px_14.155px_0px rgba(17,22,49,0.04),0px_37.512px_22.649px_0px rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[432px] right-[30px] w-[50px] h-[50px]">
          <img
            src={Shape6}
            alt="Shape"
            className="w-full h-full rotate-[18deg] rounded-[13.34px] shadow-[2.831px_5.662px_14.155px_0px rgba(17,22,49,0.04),0px_37.512px_22.649px_0px rgba(0,0,0,0.02)]"
          />
        </div>
      </div>
    </div>
  );
};

export default WizardTerms;
