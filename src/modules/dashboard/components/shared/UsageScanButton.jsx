import { Badge } from "../ui/badge";
import { RiBarChartBoxLine, RiLoader4Line, RiRefreshLine } from "react-icons/ri";
import { USAGE_AVAILABLE } from "@/lib/widgetUsage";
import ProConfirmDialog from "./ProConfirmDialog";
import { useState } from "react";
import { cn } from "@/lib/utils";
import { __ } from "@wordpress/i18n";

/**
 * "Usage" — runs the on-demand scan that counts the pages each widget is on.
 *
 * Sits on the tab row beside the Settings link, and is sized to belong there:
 * a compact outlined chip, not a full-height button. The emphasis is still a
 * step above its neighbours — it is a real action with a real cost (the scan
 * walks every `_elementor_data` row on the site) so it cannot read as a text
 * link — but it is not what anyone came to this screen to do, so it must not
 * compete with Enable All or Save either.
 *
 * SELF-CONTAINED on purpose, including its own upsell dialog: it is placed by
 * the page rather than by a top bar, so it cannot rely on a parent to own the
 * locked state.
 *
 * Locked without Pro (the scan lives in pro/inc/Usage/). The upsell is a PRO
 * badge on a live button rather than a disabled control — a control that looks
 * broken teaches nothing about why.
 *
 * @param {Function} onScan    Starts the scan.
 * @param {boolean}  scanning  Request in flight.
 * @param {boolean}  hasResult A scan has already completed this session.
 */
const UsageScanButton = ({ onScan, scanning, hasResult }) => {
  const [proOpen, setProOpen] = useState(false);

  return (
    <>
      <button
        type="button"
        disabled={scanning}
        data-aae-usage-scan
        onClick={() => (USAGE_AVAILABLE ? onScan() : setProOpen(true))}
        title={__(
          "Count the pages each widget is used on",
          "animation-addons-for-elementor"
        )}
        className={cn(
          "inline-flex items-center gap-1 h-7 px-2 rounded-md border bg-background",
          "text-xs text-text-secondary hover:text-text-secondary-hover hover:bg-background-secondary",
          "cursor-pointer whitespace-nowrap transition-colors",
          scanning && "opacity-70 cursor-default"
        )}
      >
        <span className="flex">
          {scanning ? (
            <RiLoader4Line size={13} className="animate-spin" />
          ) : hasResult ? (
            <RiRefreshLine size={13} />
          ) : (
            <RiBarChartBoxLine size={13} />
          )}
        </span>

        {scanning
          ? __("Scanning…", "animation-addons-for-elementor")
          : hasResult
            ? __("Rescan", "animation-addons-for-elementor")
            : __("Usage", "animation-addons-for-elementor")}

        {!USAGE_AVAILABLE && (
          <Badge variant="pro" className="ms-0.5">
            {__("PRO", "animation-addons-for-elementor")}
          </Badge>
        )}
      </button>

      <ProConfirmDialog open={proOpen} setOpen={setProOpen} />
    </>
  );
};

export default UsageScanButton;
