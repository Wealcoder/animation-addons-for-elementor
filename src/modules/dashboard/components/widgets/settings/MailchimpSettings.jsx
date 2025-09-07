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
import { DialogClose } from "@/components/ui/dialog";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useEffect, useRef } from "react";
import { Input } from "@/components/ui/input";

// Schema
const FormSchema = z.object({
  api_key: z.string().min(1, "API key is required"),
});

const MailchimpSettings = () => {
  const dialogCloseRef = useRef(null);
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      api_key: "",
    },
  });

  const { reset } = form;

  const getFullData = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        action: "aae_get_dynamic_settings",
        setting_name: "aae_mailchimp_api",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        reset({
          api_key: return_content.settings || "",
        });
      });
  };

  useEffect(() => {
    getFullData();
  }, []);

  async function onSubmit(data) {
    const apiKeyError = form.getFieldState("api_key").error;

    if (apiKeyError) return;

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        action: "aae_save_dynamic_settings",
        setting_name: "aae_mailchimp_api",
        form_fields: data.api_key,
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        if (dialogCloseRef.current) {
          dialogCloseRef.current.click();
        }
      });
  }

  return (
    <div className="py-5">
      <div className="px-6 pb-4 border-b border-[#F2F5F8]">
        <h2 className="text-xl text-text font-medium">Mailchimp API</h2>
        <p className="text-sm text-text-secondary mt-1">
          Add credentials to your Mailchimp
        </p>
      </div>
      <div>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <div className="px-6 py-5 space-y-4 border-b border-[#F2F5F8]">
              <FormField
                control={form.control}
                name="api_key"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <FormLabel className="text-[#0E121B]">API Key</FormLabel>
                    <div className="relative">
                      <FormControl>
                        <Input placeholder="Enter your API key" {...field} />
                      </FormControl>
                    </div>

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

export default MailchimpSettings;
