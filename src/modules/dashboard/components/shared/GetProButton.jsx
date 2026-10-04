import { useActivate } from "@/hooks/app.hooks";
import { Button, buttonVariants } from "../ui/button";
import { toast } from "sonner";
import { RiKey2Line, RiVipCrown2Line } from "react-icons/ri";
import { cn } from "@/lib/utils";
import { useEffect, useState } from "react";
import LicenseDialog from "./LicenseDialog";

/**
 * @param {string}  btnClassName Merged onto the button (twMerge, so it can
 *                               override the variant's own size classes).
 * @param {boolean} showLicense  Opens the license dialog from outside.
 * @param {boolean} upsellOnly   Render ONLY the "Get Pro Version" state, and
 *                               nothing at all once Pro is on disk. For slots
 *                               that exist purely to sell Pro — see below.
 */
const GetProButton = ({ btnClassName, showLicense, upsellOnly = false }) => {
  const { activated } = useActivate();
  const [openLicense, setOpenLicense] = useState(false);
  const role = WCF_ADDONS_ADMIN.user_role;
  const isAdmin = role.includes("administrator");

  /*
   * PHP sets this per plugin in dashboard_integrations_config():
   *   Download  — not on disk at all  (i.e. Pro does not exist)
   *   Active    — on disk, not activated
   *   Activated — on disk and running
   *
   * Optional chaining because the whole `integrations` branch is skipped
   * server-side when its config key is absent.
   */
  const proAction =
    activated?.integrations?.plugins?.elements?.[
      "animation-addon-for-elementorpro"
    ]?.action;

  useEffect(() => {
    setOpenLicense(showLicense);
  }, [showLicense]);


  const activePlugin = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_active_plugin",
        action_base:
          "animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        if (return_content?.success) {
          toast.success(return_content?.data?.message, {
            position: "top-right",
          });

          window.location.reload();
        }
      });
  };
  const getProLink = (
    <a
      href="https://animation-addons.com/"
      target="_blank"
      rel="noreferrer"
      className={cn(buttonVariants({ variant: "pro" }), btnClassName)}
    >
      <span className="me-2 flex">
        <RiVipCrown2Line size={20} />
      </span>
      Get Pro Version
    </a>
  );

  /*
   * An upsell-only slot disappears the moment Pro is installed. The other two
   * states are actions on an installed plugin ("activate it", "enter your
   * licence"), and those belong on the header, which is on every screen —
   * not on a hero banner someone lands on once. Returning null rather than an
   * empty wrapper also lets a parent's `gap` collapse cleanly.
   *
   * No LicenseDialog here: it is only reachable from the licence state, which
   * this mode never renders.
   */
  if (upsellOnly) {
    return isAdmin && proAction === "Download" ? getProLink : null;
  }

  return (
    <div>
      {isAdmin &&
        (proAction === "Active" ? (
          <Button
            variant="pro"
            onClick={() => activePlugin()}
            className={btnClassName}
          >
            <span className="me-2 flex">
              <RiVipCrown2Line size={20} />
            </span>
            Active Plugin
          </Button>
        ) : proAction === "Download" ? (
          getProLink
        ) : (
          <Button
            variant="pro"
            onClick={() => {
              setOpenLicense(true);
            }}
            className={btnClassName}
          >
            <span className="me-1.5 flex">
              <RiKey2Line size={20} />
            </span>

            {activated?.product_status?.item_id === 13
              ? "Deactivate License"
              : "Activate License"}
          </Button>
        ))}
      <LicenseDialog open={openLicense} setOpen={setOpenLicense} />
    </div>
  );
};

export default GetProButton;
