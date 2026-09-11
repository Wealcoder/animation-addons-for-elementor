import { __ } from "@wordpress/i18n";
import { useForm } from "react-hook-form";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { DialogClose } from "@/components/ui/dialog";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useEffect, useRef, useState } from "react";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toast } from "sonner";
import { RefreshCw, Check } from "lucide-react";

// Every per-filter key the "custom" mode can override. One table, so the schema,
// the defaults, the reset fallbacks and the rendered fields cannot drift apart.
// `fallback` is the placeholder only: the stored value stays EMPTY unless the
// user types something, because a pre-filled key would always win over the
// Prefix field below it and the prefix would silently do nothing.
const CUSTOM_KEYS = [
  { name: "custom_price", label: __("Price Key", "animation-addons-for-elementor"), fallback: "price" },
  { name: "custom_rating", label: __("Rating Key", "animation-addons-for-elementor"), fallback: "rating" },
  { name: "custom_stock", label: __("Stock Key", "animation-addons-for-elementor"), fallback: "stock" },
  { name: "custom_onsale", label: __("On Sale Key", "animation-addons-for-elementor"), fallback: "onsale" },
  { name: "custom_featured", label: __("Featured Key", "animation-addons-for-elementor"), fallback: "featured" },
  { name: "custom_sort", label: __("Sort Key", "animation-addons-for-elementor"), fallback: "sort" },
  { name: "custom_search", label: __("Search Key", "animation-addons-for-elementor"), fallback: "search" },
  { name: "custom_author", label: __("Author Key", "animation-addons-for-elementor"), fallback: "author" },
  { name: "custom_date", label: __("Date Key", "animation-addons-for-elementor"), fallback: "date" },
];

const emptyCustomKeys = () =>
  Object.fromEntries(CUSTOM_KEYS.map((k) => [k.name, ""]));

// Schema
const FormSchema = z.object({
  param_mode: z.enum(["clean", "prefixed", "custom"]),
  custom_prefix: z.string().optional(),
  ...Object.fromEntries(CUSTOM_KEYS.map((k) => [k.name, z.string().optional()])),
});

const LoopGridSettings = () => {
  const dialogCloseRef = useRef(null);
  const [isSaving, setIsSaving] = useState(false);
  const [isFlushing, setIsFlushing] = useState(false);

  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      param_mode: "clean",
      custom_prefix: "",
      ...emptyCustomKeys(),
    },
  });

  const { reset, watch } = form;
  const paramMode = watch("param_mode");

  const getFullData = async () => {
    try {
      const res = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_get_dynamic_settings",
          setting_name: "aae_loop_grid_settings",
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });
      const data = await res.json();
      if (data && data.settings) {
        const s = typeof data.settings === "string" ? JSON.parse(data.settings) : data.settings;
        reset({
          param_mode: s.param_mode || "clean",
          custom_prefix: s.custom_prefix || "",
          ...Object.fromEntries(CUSTOM_KEYS.map((k) => [k.name, s[k.name] || ""])),
        });
      }
    } catch (err) {
      console.error("Failed to load loop grid settings", err);
    }
  };

  useEffect(() => {
    getFullData();
  }, []);

  const handleFlushCache = async () => {
    setIsFlushing(true);
    try {
      const res = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_flush_known_taxonomies",
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });
      const data = await res.json();
      if (data.success) {
        toast.success(
          data.data?.message || __("Done.", "animation-addons-for-elementor")
        );
      } else {
        toast.error(data.data?.message || __("Could not update the remembered taxonomies.", "animation-addons-for-elementor"));
      }
    } catch (e) {
      toast.error(__("Network error.", "animation-addons-for-elementor"));
    } finally {
      setIsFlushing(false);
    }
  };

  async function onSubmit(data) {
    setIsSaving(true);
    try {
      const res = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_save_dynamic_settings",
          setting_name: "aae_loop_grid_settings",
          form_fields: JSON.stringify(data),
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });
      const result = await res.json();
      toast.success(__("Settings updated successfully.", "animation-addons-for-elementor"));
      if (dialogCloseRef.current) {
        dialogCloseRef.current.click();
      }
    } catch (err) {
      toast.error(__("Failed to save settings.", "animation-addons-for-elementor"));
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <div className="py-5 max-w-[560px]">
      <div className="px-6 pb-4 border-b border-[#F2F5F8]">
        <h2 className="text-xl text-text font-medium">
          {__("Loop Grid Settings", "animation-addons-for-elementor")}
        </h2>
        <p className="text-sm text-text-secondary mt-1">
          {__(
            "Choose how filter parameters look in visitor URLs, and manage the taxonomies the Loop Grid remembers.",
            "animation-addons-for-elementor"
          )}
        </p>
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)}>
          <div className="px-6 py-5 space-y-5 border-b border-[#F2F5F8] max-h-[60vh] overflow-y-auto">
            {/* Filter URL Mode */}
            <FormField
              control={form.control}
              name="param_mode"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-[#0E121B] font-medium">
                    {__("Filter URL Parameter Mode", "animation-addons-for-elementor")}
                  </FormLabel>
                  <Select onValueChange={field.onChange} value={field.value}>
                    <FormControl>
                      <SelectTrigger className="w-full">
                        <SelectValue
                          placeholder={__("Select parameter mode", "animation-addons-for-elementor")}
                        />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <SelectItem value="clean">
                        {__("Clean / Human-Friendly (e.g. ?price=10..50&rating=4)", "animation-addons-for-elementor")}
                      </SelectItem>
                      <SelectItem value="prefixed">
                        {__("Plugin Prefixed (e.g. ?aae_price=10..50&aae_rating=4)", "animation-addons-for-elementor")}
                      </SelectItem>
                      <SelectItem value="custom">
                        {__("Custom Parameter Keys", "animation-addons-for-elementor")}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <FormDescription className="text-xs text-text-secondary">
                    {field.value === "clean" &&
                      __(
                        "Removes plugin prefixes from visitor URLs. A name WordPress already uses — author, search, or a taxonomy that owns its own query string — keeps its aae_ prefix, because borrowing one makes the page itself 404. Older links using the aae_ keys keep working either way.",
                        "animation-addons-for-elementor"
                      )}
                    {field.value === "prefixed" &&
                      __(
                        "Keeps the original 'aae_' prefixes on all query parameters.",
                        "animation-addons-for-elementor"
                      )}
                    {field.value === "custom" &&
                      __(
                        "Define exact parameter slugs for price, rating, stock, sort, and other filter elements.",
                        "animation-addons-for-elementor"
                      )}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Custom URL Keys (Conditional) */}
            {paramMode === "custom" && (
              <div className="p-4 bg-background-secondary rounded-lg space-y-3 border border-[#E2E8F0]">
                <h4 className="text-sm font-semibold text-text">
                  {__("Custom URL Keys", "animation-addons-for-elementor")}
                </h4>
                <p className="text-xs text-text-secondary">
                  {__(
                    "Leave a key blank to use the prefix with its normal name. Type a key and it is used as-is, prefix and all, for that filter only.",
                    "animation-addons-for-elementor"
                  )}
                </p>

                <div className="grid grid-cols-2 gap-3">
                  <FormField
                    control={form.control}
                    name="custom_prefix"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Prefix (optional)", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="e.g. f" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  {CUSTOM_KEYS.map((k) => (
                    <FormField
                      key={k.name}
                      control={form.control}
                      name={k.name}
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="text-xs text-text">{k.label}</FormLabel>
                          <FormControl>
                            <Input placeholder={k.fallback} {...field} />
                          </FormControl>
                        </FormItem>
                      )}
                    />
                  ))}
                </div>
              </div>
            )}

            {/* Cache Management */}
            <div className="pt-2">
              <div className="flex items-center justify-between p-3.5 bg-background-secondary rounded-lg border border-[#E2E8F0]">
                <div>
                  <h4 className="text-sm font-medium text-text">
                    {__("Remembered Taxonomies", "animation-addons-for-elementor")}
                  </h4>
                  <p className="text-xs text-text-secondary mt-0.5">
                    {__(
                      "Taxonomies whose plugin is switched off are remembered so pages keep their saved filters. Forget the ones no page uses any more. Newly registered taxonomies appear on their own and need no action here.",
                      "animation-addons-for-elementor"
                    )}
                  </p>
                </div>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={handleFlushCache}
                  disabled={isFlushing}
                  className="flex items-center gap-1.5 text-xs h-9"
                >
                  <RefreshCw className={`w-3.5 h-3.5 ${isFlushing ? "animate-spin" : ""}`} />
                  {isFlushing
                    ? __("Checking...", "animation-addons-for-elementor")
                    : __("Forget Unused", "animation-addons-for-elementor")}
                </Button>
              </div>
            </div>
          </div>

          {/* Action buttons */}
          <div className="px-6 pt-4 flex gap-3 justify-end items-center">
            <DialogClose asChild ref={dialogCloseRef}>
              <Button
                type="button"
                variant="secondary"
                className="h-10 text-sm px-4"
              >
                {__("Cancel", "animation-addons-for-elementor")}
              </Button>
            </DialogClose>
            <Button
              type="submit"
              disabled={isSaving}
              className="h-10 text-sm px-6"
            >
              {isSaving
                ? __("Saving...", "animation-addons-for-elementor")
                : __("Save Settings", "animation-addons-for-elementor")}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  );
};

export default LoopGridSettings;
