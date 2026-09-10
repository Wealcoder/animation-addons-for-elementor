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

// Schema
const FormSchema = z.object({
  param_mode: z.enum(["clean", "prefixed", "custom"]),
  custom_prefix: z.string().optional(),
  custom_price: z.string().optional(),
  custom_rating: z.string().optional(),
  custom_stock: z.string().optional(),
  custom_search: z.string().optional(),
  custom_sort: z.string().optional(),
  custom_author: z.string().optional(),
  custom_date: z.string().optional(),
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
      custom_price: "price",
      custom_rating: "rating",
      custom_stock: "stock",
      custom_search: "search",
      custom_sort: "sort",
      custom_author: "author",
      custom_date: "date",
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
          custom_price: s.custom_price || "price",
          custom_rating: s.custom_rating || "rating",
          custom_stock: s.custom_stock || "stock",
          custom_search: s.custom_search || "search",
          custom_sort: s.custom_sort || "sort",
          custom_author: s.custom_author || "author",
          custom_date: s.custom_date || "date",
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
          data.data?.message || __("Taxonomy cache flushed successfully.", "animation-addons-for-elementor")
        );
      } else {
        toast.error(__("Failed to flush taxonomy cache.", "animation-addons-for-elementor"));
      }
    } catch (e) {
      toast.error(__("Network error while flushing cache.", "animation-addons-for-elementor"));
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
            "Customize URL filter parameters and manage taxonomy cache for Loop Grid.",
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
                        {__("Clean / Human-Friendly (e.g. ?price=10-50&rating=4)", "animation-addons-for-elementor")}
                      </SelectItem>
                      <SelectItem value="prefixed">
                        {__("Plugin Prefixed (e.g. ?aae_price=10-50&aae_rating=4)", "animation-addons-for-elementor")}
                      </SelectItem>
                      <SelectItem value="custom">
                        {__("Custom Parameter Keys", "animation-addons-for-elementor")}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <FormDescription className="text-xs text-text-secondary">
                    {field.value === "clean" &&
                      __(
                        "Removes plugin prefixes from visitor URLs. Shared links with legacy 'aae_' keys are automatically supported for backwards compatibility.",
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
                          <Input placeholder="e.g. f_ or empty" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="custom_price"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Price Key", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="price" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="custom_rating"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Rating Key", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="rating" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="custom_stock"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Stock Key", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="stock" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="custom_sort"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Sort Key", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="sort" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="custom_search"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Search Key", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="search" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="custom_author"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Author Key", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="author" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="custom_date"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-text">
                          {__("Date Key", "animation-addons-for-elementor")}
                        </FormLabel>
                        <FormControl>
                          <Input placeholder="date" {...field} />
                        </FormControl>
                      </FormItem>
                    )}
                  />
                </div>
              </div>
            )}

            {/* Cache Management */}
            <div className="pt-2">
              <div className="flex items-center justify-between p-3.5 bg-background-secondary rounded-lg border border-[#E2E8F0]">
                <div>
                  <h4 className="text-sm font-medium text-text">
                    {__("Taxonomy Cache", "animation-addons-for-elementor")}
                  </h4>
                  <p className="text-xs text-text-secondary mt-0.5">
                    {__(
                      "Flush cached public taxonomies when new CPTs or taxonomies are registered.",
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
                    ? __("Flushing...", "animation-addons-for-elementor")
                    : __("Flush Cache", "animation-addons-for-elementor")}
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
