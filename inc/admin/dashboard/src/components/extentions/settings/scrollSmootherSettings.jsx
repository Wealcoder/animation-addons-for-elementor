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
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { DialogClose } from "@/components/ui/dialog";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useRef, useState } from "react";

const FormSchema = z.object({
  smooth: z.coerce
    .number({
      invalid_type_error: "Smooth must be a number",
    })
    .optional(),
  mobile: z.boolean().optional(),
  media: z.string().regex(/^(?:\d+px|min-width:\s?\d+px|max-width:\s?\d+px)$/, {
    message:
      "Invalid format. Use '900px', 'min-width: 800px', or 'max-width: 1024px'.",
  }),
});

const ScrollSmootherSettings = () => {
  const dialogCloseRef = useRef(null);

  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      smooth: WCF_ADDONS_ADMIN?.smoothScroller?.smooth || 1.35,
      mobile:
        WCF_ADDONS_ADMIN?.smoothScroller?.mobile === "true" ? true : false,
      media: WCF_ADDONS_ADMIN?.smoothScroller?.media || "768px",
    },
  });

  const convertToMinWidth = (value) => {
    if (/^\d+px$/.test(value)) {
      return `min-width: ${value}`;
    }
    return value;
  };

  async function onSubmit(data) {
    const convertedMedia = convertToMinWidth(data.media);

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "save_smooth_scroller_settings",
        mobile: data.mobile,
        smooth: data.smooth,
        media: convertedMedia,
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        WCF_ADDONS_ADMIN.smoothScroller = JSON.parse(return_content);
        if (dialogCloseRef.current) {
          dialogCloseRef.current.click();
        }
      });
  }

  return (
    <div className="py-5">
      <div className="px-6 pb-4 border-b border-[#F2F5F8]">
        <h2 className="text-xl text-text font-medium">Smooth Scroller</h2>
        <p className="text-sm text-text-secondary mt-1">
          Enter Smooth Scroller value below.
        </p>
      </div>
      <div>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <div className="px-6 py-5 space-y-4 border-b border-[#F2F5F8]">
              <FormField
                control={form.control}
                name="smooth"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-text">Smooth Value</FormLabel>
                    <FormControl>
                      <Input
                        placeholder="Enter Value"
                        type="number"
                        className="h-11 text-base"
                        {...field}
                      />
                    </FormControl>

                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="mobile"
                render={({ field }) => (
                  <FormItem className="flex items-center space-x-3 space-y-0">
                    <FormControl>
                      <Checkbox
                        checked={!!field.value}
                        onCheckedChange={field.onChange}
                      />
                    </FormControl>
                    <div className="space-y-1.5 leading-none">
                      <FormLabel className="text-[#0E121B]">
                        Enable on mobile
                      </FormLabel>
                    </div>
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="media"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-text">Media</FormLabel>
                    <FormControl>
                      <Input
                        placeholder="768px"
                        className="h-11 text-base"
                        {...field}
                      />
                    </FormControl>
                    <FormDescription>
                      {`Ex: 900px or min-width: 800px or max-width: 1024px`}
                    </FormDescription>

                    <FormMessage />
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
              {/* <DialogClose asChild> */}
              <Button
                type="submit"
                className="h-11 shadow-common-2 text-base px-6"
              >
                Save
              </Button>
              {/* </DialogClose> */}
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
};

export default ScrollSmootherSettings;
