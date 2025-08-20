import { useForm } from "react-hook-form";

import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
} from "@/components/ui/form";
import { Checkbox } from "@/components/ui/checkbox";
import { DialogClose } from "@/components/ui/dialog";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useRef } from "react";

const FormSchema = z.object({
  load_in_head: z.boolean().optional(),
});

const CustomFontSettings = () => {
  const dialogCloseRef = useRef(null);
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      load_in_head: WCF_ADDONS_ADMIN?.cf_settings?.load_in_head ?? false,
    },
  });

  async function onSubmit(data) {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_addon_custom_font_settings",
        settings: JSON.stringify( data ),
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
     
        WCF_ADDONS_ADMIN.cf_settings = JSON.parse( return_content );
        if (dialogCloseRef.current) {
          dialogCloseRef.current.click();
        }
      });
  }

  return (
    <div className="py-5">
      <div className="px-6 pb-4 border-b border-[#F2F5F8]">
        <h2 className="text-xl text-text font-medium">Font Load in Head</h2>
        <p className="text-sm text-text-secondary mt-1">
          Check for load font in head
        </p>
      </div>
      <div>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <div className="px-6 py-5 space-y-4 border-b border-[#F2F5F8]">
              <FormField
                control={form.control}
                name="load_in_head"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <div className="flex items-center space-x-3 ">
                      <FormControl>
                        <Checkbox
                          checked={!!field.value}
                          onCheckedChange={field.onChange}
                        />
                      </FormControl>
                      <div className="space-y-1.5 leading-none">
                        <FormLabel className="text-[#0E121B]">
                          Load Head
                        </FormLabel>
                      </div>
                    </div>
                    <FormDescription>
                      {`The font will be loaded in the head section`}
                    </FormDescription>
                  </FormItem>
                )}
              />
            </div>

            <div className="px-8 pt-4 flex gap-3 justify-end items-center">
              <DialogClose asChild ref={dialogCloseRef}>
                <Button
                  variant="secondary"
                  className="h-11 shadow-common-2 text-base px-[18px]"
                >
                  Cancel
                </Button>
              </DialogClose>
              <Button
                type="submit"
                className="h-11 shadow-common-2 text-base px-6"
              >
                Save
              </Button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
};

export default CustomFontSettings;
