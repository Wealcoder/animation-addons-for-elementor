import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { buttonVariants } from "../ui/button";
import { Input } from "../ui/input";
import { Label } from "../ui/label";
import { Textarea } from "../ui/textarea";
import SubmitIcon from "../../../../../public/images/submit-icon.png";

const initialForm = { name: "", email: "", feature: "" };

/**
 * Posts to the `wcf_request_new_feature` admin-ajax action
 * (Dashboard::request_new_feature in inc/admin/dashboard.php), which relays
 * the submission server-to-server to animation-addons.com. The shared key
 * lives in PHP for exactly that reason — it must never reach this bundle.
 *
 * Errors are surfaced verbatim when the far end sends a message, so a 429
 * reads as "wait a moment" rather than a generic failure.
 */
const RequestFeatureForm = () => {
  const [form, setForm] = useState(initialForm);
  const [submitting, setSubmitting] = useState(false);

  const handleChange = (field) => (e) => {
    setForm((prev) => ({ ...prev, [field]: e.target.value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "wcf_request_new_feature",
          name: form.name,
          email: form.email,
          feature: form.feature,
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      }).then((res) => res.json());

      if (response?.success) {
        toast.success(
          response.data ||
            __(
              "Thanks! Your feature request has been submitted.",
              "animation-addons-for-elementor",
            ),
          { position: "top-right" },
        );
        setForm(initialForm);
      } else {
        toast.error(
          response?.data ||
            __(
              "Something went wrong. Please try again.",
              "animation-addons-for-elementor",
            ),
          { position: "top-right" },
        );
      }
    } catch (error) {
      toast.error(
        __(
          "Something went wrong. Please try again.",
          "animation-addons-for-elementor",
        ),
        { position: "top-right" },
      );
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="border rounded-2xl p-5 shadow-common h-full">
      <p className="text-lg font-medium text-text">
        {__("Request New Feature", "animation-addons-for-elementor")}
      </p>
      <form onSubmit={handleSubmit} className="flex flex-col gap-6 mt-6">
        <div className="flex flex-col sm:flex-row sm:items-center gap-6">
          <div className="flex flex-col gap-3 flex-1">
            <Label htmlFor="feature-name" className="text-xs text-text">
              {__("Name", "animation-addons-for-elementor")}
            </Label>
            <Input
              id="feature-name"
              placeholder={__(
                "Your name",
                "animation-addons-for-elementor",
              )}
              className="bg-background-secondary"
              value={form.name}
              onChange={handleChange("name")}
              required
            />
          </div>
          <div className="flex flex-col gap-3 flex-1">
            <Label htmlFor="feature-email" className="text-xs text-text">
              {__("Mail Address", "animation-addons-for-elementor")}
            </Label>
            <Input
              id="feature-email"
              type="email"
              placeholder="example@mail.com"
              className="bg-background-secondary"
              value={form.email}
              onChange={handleChange("email")}
              required
            />
          </div>
        </div>
        <div className="flex flex-col gap-3">
          <Label htmlFor="feature-idea" className="text-xs text-text">
            {__("Feature Idea", "animation-addons-for-elementor")}
          </Label>
          <Textarea
            id="feature-idea"
            placeholder={__(
              "Feature description",
              "animation-addons-for-elementor",
            )}
            className="min-h-[215px] resize-none bg-background-secondary"
            value={form.feature}
            onChange={handleChange("feature")}
            required
          />
        </div>
        <button
          type="submit"
          disabled={submitting}
          className={cn(buttonVariants({ variant: "secondary" }), "w-fit")}
        >
          {__("Submit", "animation-addons-for-elementor")}
          <img src={SubmitIcon} alt="" className="w-3 h-3 ms-1" />
        </button>
      </form>
    </div>
  );
};

export default RequestFeatureForm;
