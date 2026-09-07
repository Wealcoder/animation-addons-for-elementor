import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "../ui/button";
import { RiLayoutGridLine, RiShieldCheckLine } from "react-icons/ri";
import { __, sprintf } from "@wordpress/i18n";

/**
 * Shown ONCE per V4 template import, and only when the site already holds
 * Elementor V4 content.
 *
 * It is not a warning about damage. A second V4 import cannot break the first:
 * the importer runs the design system through Elementor's template-library
 * chain in `keep_create` mode, which creates every class and variable as a
 * NEW entry and renames a label clash with a `DUP_` prefix (see
 * inc/admin/atomic-kit-import.php). What the user needs to know is the
 * consequence of that — the two demos' classes coexist rather than merge, so
 * the site's class list grows — and that nothing they built is touched.
 *
 * Hence the calm tint (indigo, the same hue the V4 badge on the card uses)
 * rather than DisableAllV3Dialog's danger red: red would borrow alarm this
 * action has not earned. One message, Continue / Cancel, no mode picker —
 * for a full template the importer decides the mode from the same signal
 * this dialog is gated on, and offering a choice the server would then
 * override is a control that lies.
 *
 * @param {boolean}  open      Dialog visibility.
 * @param {Function} setOpen   Visibility setter.
 * @param {Function} onConfirm Runs only if the user goes ahead.
 * @param {string}   title     The template's title, so the copy names it.
 */
const V4ImportDialog = ({ open, setOpen, onConfirm, title = "" }) => {
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      {/*
        DialogContent ships `bg-transparent` — a caller that passes no
        background renders as floating controls over the overlay.
      */}
      <DialogContent
        className="w-[460px] bg-background rounded-2xl overflow-hidden shadow-auth-card"
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
                  {__(
                    "This site already uses Elementor V4",
                    "animation-addons-for-elementor"
                  )}
                </DialogTitle>
                <DialogDescription className="text-sm text-label text-start">
                  {title
                    ? sprintf(
                        /* translators: %s: the starter template's title. */
                        __(
                          "The design system of “%s” will be added beside what is already here.",
                          "animation-addons-for-elementor"
                        ),
                        title
                      )
                    : __(
                        "This template's design system will be added beside what is already here.",
                        "animation-addons-for-elementor"
                      )}
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          <div className="mt-5 rounded-xl bg-[#EAEAFF]/60 border border-[#D6D6FF] p-4 text-sm text-[#2E2E8F] space-y-2">
            <p>
              {__(
                "Its global classes and variables are imported as new entries. Your existing classes, variables and pages are not changed.",
                "animation-addons-for-elementor"
              )}
            </p>
            <p>
              {__(
                "If a class name is already taken here, the imported copy is renamed with a “DUP_” prefix so both keep their styling.",
                "animation-addons-for-elementor"
              )}
            </p>
          </div>

          <p className="mt-4 flex items-start gap-2 text-xs text-text-secondary">
            <span className="flex shrink-0 mt-0.5">
              <RiShieldCheckLine size={14} />
            </span>
            {__(
              "Nothing is replaced. Unused imported classes can be deleted later from Elementor's Class Manager.",
              "animation-addons-for-elementor"
            )}
          </p>
        </div>

        <div className="flex justify-end gap-2 px-6 py-4 bg-background-secondary border-t">
          <Button
            variant="secondary"
            onClick={() => setOpen(false)}
            data-aae-v4-import-cancel
          >
            {__("Cancel", "animation-addons-for-elementor")}
          </Button>
          <Button
            data-aae-v4-import-confirm
            onClick={() => {
              setOpen(false);
              onConfirm();
            }}
          >
            {__("Continue import", "animation-addons-for-elementor")}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default V4ImportDialog;
