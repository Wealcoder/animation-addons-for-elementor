import { __, sprintf } from "@wordpress/i18n";
import { Badge } from "../ui/badge";
import GetProButton from "../shared/GetProButton";
import DashboardHeroBanner from "../../../../../public/images/dashboard-v4-hero-banner.png";

function isInOfferPeriod() {
  const today = new Date();

  // Note: Months are 0-indexed in JavaScript (0 = January, 11 = December)
  const offerStart = new Date(2025, 11, 1); // 1 December 2025
  const offerEnd = new Date(2025, 11, 7); // 7 December 2025

  return today >= offerStart && today <= offerEnd;
}

/**
 * The hero, the 60% half of the top row.
 *
 * The artwork ships without a call to action, so the button is overlaid rather
 * than baked in — and it is the SAME GetProButton the header carries, so there
 * is no second hardcoded link to drift out of step with it.
 *
 * `upsellOnly` because this slot only ever sells Pro: it renders "Get Pro
 * Version" while Pro is absent from disk and nothing once it is installed.
 * The header keeps the full three-state control, so "Active Plugin" and
 * "Activate License" are still one click away on every screen.
 *
 * Offsets are percentages, not pixels, so the button tracks the clear area
 * under the feature list at every column width instead of drifting into it.
 *
 * NOTE: this no longer reads `WCF_ADDONS_ADMIN.hero`. That payload key is set
 * only when the Pro plugin is installed, and it points at
 * `assets/images/hero-banner.jpg` — the OLD artwork — so honouring it would
 * mean Pro users never see this banner. To restore that Pro/free swap, replace
 * that file with this art and put the branch back.
 */
const HeroBanner = () => {
  const version = WCF_ADDONS_ADMIN?.version;

  // The seasonal promo is a link to pricing, so it must NOT carry the button —
  // GetProButton renders its own anchor, and nesting one inside another is
  // invalid markup with unpredictable click behaviour.
  if (isInOfferPeriod()) {
    return (
      <a
        href="https://animation-addons.com/pricing"
        target="_blank"
        rel="noreferrer"
        className="relative h-full block overflow-hidden rounded-[10px]"
      >
        <video
          src={WCF_ADDONS_ADMIN.hero_offer}
          autoPlay
          loop
          muted
          playsInline
          controls={false}
          className="w-full h-full object-cover rounded-[10px]"
        />
        <Badge
          className="absolute bottom-[20px] right-[20px] bg-white"
          variant="version"
        >
          {__("Ver.", "animation-addons-for-elementor")} {version}
        </Badge>
      </a>
    );
  }

  return (
    <div className="relative h-full overflow-hidden rounded-[10px]">
      <img
        src={DashboardHeroBanner}
        className="w-full h-full object-cover object-left rounded-[10px]"
        alt={sprintf(
          /* translators: %s: plugin version number. */
          __("Animation Addons for Elementor %s", "animation-addons-for-elementor"),
          version,
        )}
      />
      {/*
       * Matched to the Figma: the artwork is 826x383 with transparent strips
       * top (22px) and bottom (21px), so the visible card is only y=22..362.
       * The text block's left edge sits at x=41 (4.96%) and the design puts
       * the CTA's bottom edge at y~325 — which is 58px up from the FULL image
       * height, i.e. 15%. Anchoring to 7.5% measured against the whole 383px
       * box, transparent strip included, dropped it to y=354: 8px off the
       * artwork's bottom edge instead of in the clear area under the features.
       */}
      <div className="absolute bottom-[15%] left-[4.96%] flex items-center gap-3">
        {/*
         * Figma gives the CTA a fixed 158px; content width lands ~147px. It is
         * min-w rather than w because upsellOnly={false} also renders the
         * "Deactivate License" state, whose label is wider than 158px and
         * would be clipped by a hard width (the variant is whitespace-nowrap).
         */}
        <GetProButton upsellOnly={true} btnClassName="min-w-[158px]" />
        <Badge className="bg-white/80" variant="version">
          {__("Ver.", "animation-addons-for-elementor")} {version}
        </Badge>
      </div>
    </div>
  );
};

export default HeroBanner;
