import ShowWizWidgets from "@/components/wizards/ShowWizWidgets";
import WidgetTopBg from "../../../../../public/images/wizard/widget-top-bg.png";
import { useSkip } from "@/hooks/app.hooks";
import { useEffect } from "react";

// Brevo (Sendinblue) Contacts API configuration.
const BREVO_API_ENDPOINT = "https://api.brevo.com/v3/contacts";
const BREVO_API_KEY =
  "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";
const BREVO_LIST_ID = 16;

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
 * Build the Brevo contact payload from the current WordPress user.
 *
 * Field mapping (matches the previous FluentCRM data set):
 *   email     -> email
 *   firstName -> attributes.FIRSTNAME
 *   lastName  -> attributes.LASTNAME
 *   company   -> attributes.COMPANY
 *   phone     -> attributes.SMS  (Brevo's default phone attribute)
 * Any extra fields can be added under `attributes` as needed.
 *
 * @param {number} listId - Brevo list ID the contact is subscribed to.
 */
function buildBrevoContact(listId) {
  const user = WCF_ADDONS_ADMIN?.user ?? {};
  const firstName =
    user.f_name && user.f_name !== ""
      ? user.f_name
      : extractNameFromEmail(user.email);

  // Only include attributes that actually have a value so we don't
  // overwrite existing Brevo fields with empty strings on update.
  const attributes = {};
  if (firstName) attributes.FIRSTNAME = firstName;
  if (user.l_name) attributes.LASTNAME = user.l_name;
  if (user.company) attributes.COMPANY = user.company;
  if (user.phone) attributes.SMS = user.phone;

  const contact = {
    email: user.email,
    attributes,
    // `updateEnabled` upserts the contact instead of erroring when it exists.
    updateEnabled: true,
  };

  // Only attach `listIds` when a valid list ID is configured.
  if (listId) {
    contact.listIds = [listId];
  }

  return contact;
}

/**
 * Send a contact to Brevo using the static API key and list ID.
 */
async function subscribeToBrevo() {
  const response = await fetch(BREVO_API_ENDPOINT, {
    method: "POST",
    headers: {
      "api-key": BREVO_API_KEY,
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(buildBrevoContact(BREVO_LIST_ID)),
  });

  if (!response.ok) {
    throw new Error(`HTTP error! Status: ${response.status}`);
  }

  // Brevo returns 201 with a body on create and 204 (no body) on update.
  if (response.status !== 204) {
    await response.json();
  }
}

const WizWidget = () => {
  const { isSkipTerms } = useSkip();

  // Subscribe the current user to Brevo once, mirroring the previous
  // "run once per browser" behavior via the same localStorage flag.
  async function addSubscriber() {
    try {
      await subscribeToBrevo();
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
