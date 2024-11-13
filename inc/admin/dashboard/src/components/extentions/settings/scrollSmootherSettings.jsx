import { useForm } from "react-hook-form";

import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { DialogClose } from "@/components/ui/dialog";

const ScrollSmootherSettings = () => {
  const form = useForm({
    defaultValues: {
      smooth: WCF_ADDONS_ADMIN.smoothScroller.smooth || 1.35,
      mobile: WCF_ADDONS_ADMIN.smoothScroller.mobile || false,
    },
  });
  console.log(WCF_ADDONS_ADMIN.smoothScroller);

  async function onSubmit(data) {
    console.log(data);
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
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        WCF_ADDONS_ADMIN.smoothScroller = JSON.parse(return_content);
        console.log(return_content);
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
            </div>

            <div className="px-8 pt-4 flex gap-3 justify-end items-center">
              <DialogClose asChild>
                <Button
                  variant="secondary"
                  className="h-11 shadow-common-2 text-base px-[18px]"
                >
                  Cancel
                </Button>
              </DialogClose>
              <DialogClose asChild>
                <Button
                  type="submit"
                  className="h-11 shadow-common-2 text-base px-6"
                >
                  Save
                </Button>
              </DialogClose>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
};

export default ScrollSmootherSettings;
