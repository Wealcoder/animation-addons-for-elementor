import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "C/components/ui/dialog";
import { Button } from "C/components/ui/button";
import { RadioGroup, RadioGroupItem } from "C/components/ui/radio-group";
import { RiLayoutGridLine, RiShieldCheckLine } from "react-icons/ri";
import { PAGE_MODE_KEEP, PAGE_MODE_MATCH } from "C/lib/atomicImport";

/**
 * Shown ONCE per V4 page import, and only when the site already holds
 * Elementor V4 content.
 *
 * Twin of the dashboard's V4ImportDialog with one addition: a page is small
 * enough that the user may genuinely want it to adopt the site's own design
 * rather than bring its own, so the mode is theirs to pick here. The default
 * is "keep" — the page arrives looking exactly like its preview — because a
 * page whose classes silently resolve to a different site's values arrives
 * looking wrong with nothing to explain it. `match_site` is the opt-in.
 *
 * The value chosen rides the URL as `v4mode` through Required Features and
 * Demo Importing, and lands in the importer as `aae_page_mode`.
 *
 * @param {boolean}  open      Dialog visibility.
 * @param {Function} setOpen   Visibility setter.
 * @param {Function} onConfirm Called with the chosen mode if the user goes ahead.
 * @param {string}   title     The page's title, so the copy names it.
 * @param {string}   mode      Current mode value.
 * @param {Function} setMode   Mode setter.
 */
const V4ImportDialog = ({
  open,
  setOpen,
  onConfirm,
  title = "",
  mode = PAGE_MODE_KEEP,
  setMode,
}) => {
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent
        className="w-[480px] bg-background rounded-2xl overflow-hidden shadow-auth-card"
        data-aae-v4-import-dialog
      >
        <div className="p-6">
          <DialogHeader>
            <div className="flex items-start gap-3.5">
              <span className="shrink-0 h-11 w-11 rounded-full bg-[#EAEAFF] border border-[#D6D6FF] flex items-center justify-center text-[#5453FD]">
                <RiLayoutGridLine size={22} />
              </span>
              <div className="flex flex-col gap-1 pt-0.5">
                <DialogTitle className="text-base font-medium text-start leading-snug">
                  This site already uses Elementor V4
                </DialogTitle>
                <DialogDescription className="text-sm text-label text-start">
                  {title
                    ? `Choose how the design of “${title}” should meet what is already here.`
                    : "Choose how this page's design should meet what is already here."}
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          <RadioGroup
            value={mode}
            onValueChange={setMode}
            className="mt-5 gap-2.5"
            data-aae-v4-page-mode
          >
            <label
              htmlFor="aae-v4-mode-keep"
              className="flex items-start gap-3 rounded-xl border border-[#D6D6FF] bg-[#EAEAFF]/60 p-4 cursor-pointer has-[[data-state=checked]]:border-[#5453FD]"
            >
              <RadioGroupItem
                value={PAGE_MODE_KEEP}
                id="aae-v4-mode-keep"
                className="mt-0.5"
              />
              <span className="flex flex-col gap-1">
                {/* The wrapping <label> already owns the click; a nested
                    <label> would be invalid HTML and doubles the target. */}
                <span className="text-sm font-medium leading-none">
                  Keep the page's original design (recommended)
                </span>
                <span className="text-xs text-text-secondary">
                  Its classes and variables are added as new entries. A name
                  already taken here is imported with a “DUP_” prefix, so both
                  keep their styling.
                </span>
              </span>
            </label>
            <label
              htmlFor="aae-v4-mode-match"
              className="flex items-start gap-3 rounded-xl border border-border p-4 cursor-pointer has-[[data-state=checked]]:border-[#5453FD] has-[[data-state=checked]]:bg-[#EAEAFF]/60"
            >
              <RadioGroupItem
                value={PAGE_MODE_MATCH}
                id="aae-v4-mode-match"
                className="mt-0.5"
              />
              <span className="flex flex-col gap-1">
                <span className="text-sm font-medium leading-none">
                  Match my site's design
                </span>
                <span className="text-xs text-text-secondary">
                  Where a class or variable with the same name already exists
                  here, the page uses yours instead of its own.
                </span>
              </span>
            </label>
          </RadioGroup>

          <p className="mt-4 flex items-start gap-2 text-xs text-text-secondary">
            <span className="flex shrink-0 mt-0.5">
              <RiShieldCheckLine size={14} />
            </span>
            Either way, your existing classes, variables and pages are not
            changed.
          </p>
        </div>

        <div className="flex justify-end gap-2 px-6 py-4 bg-background-secondary border-t">
          <Button
            variant="secondary"
            onClick={() => setOpen(false)}
            data-aae-v4-import-cancel
          >
            Cancel
          </Button>
          <Button
            data-aae-v4-import-confirm
            onClick={() => {
              setOpen(false);
              onConfirm(mode);
            }}
          >
            Continue import
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default V4ImportDialog;
