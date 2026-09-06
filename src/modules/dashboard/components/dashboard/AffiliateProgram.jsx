import { __ } from "@wordpress/i18n";
import { cn } from "@/lib/utils";
import { RiArrowRightUpLine } from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import AffiliateBanner from "../../../../../public/images/affiliate-program.jpg";

/**
 * The artwork carries the headline, the body copy, the illustration and the
 * earn chip, so all of that markup is gone — keeping a text copy underneath
 * would have meant two sources of wording to keep in step, and any drift
 * between them shows up as a mismatch on screen.
 *
 * What the artwork does NOT carry is the call to action, so the button is
 * overlaid. Offsets are percentages, not pixels, so it tracks the clear band
 * under the copy at every column width rather than drifting into it.
 *
 * The headline and copy are baked into the image, which makes them invisible
 * to a screen reader — hence the descriptive `alt`. It repeats the artwork's
 * own wording deliberately; that is the one place a second copy is correct.
 */
const AffiliateProgram = () => {
  return (
    <div className="relative overflow-hidden rounded-2xl">
      <img
        src={AffiliateBanner}
        className="w-full h-auto"
        alt={__(
          "Affiliate Program — earn more while you share what you love. Join now, share your link, and start earning with every sale.",
          "animation-addons-for-elementor",
        )}
      />
      <a
        href={"https://animation-addons.com/affiliate-terms-and-conditions/"}
        target="_blank"
        rel="noreferrer"
        className={cn(
          buttonVariants({ variant: "secondary" }),
          "absolute bottom-[8%] left-[7.3%]",
        )}
      >
        {__("Join Now", "animation-addons-for-elementor")}
        <RiArrowRightUpLine
          size={16}
          className="ms-1 rtl:rotate-360 rtl:scale-x-[-1]"
        />
      </a>
    </div>
  );
};

export default AffiliateProgram;
