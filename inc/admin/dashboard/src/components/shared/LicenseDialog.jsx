import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { RiInformation2Fill } from "react-icons/ri";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { Separator } from "../ui/separator";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "../ui/tooltip";
import { toast } from "sonner";
import { useState } from "react";
import { Loader2 } from "lucide-react";
import { useActivate, useNotification } from "@/hooks/app.hooks";

const FormSchema = z.object({
  email: z.string().email(),
  license: z.string().min(1, {
    message: "Please enter your license",
  }),
});

const LicenseDialog = ({ open, setOpen }) => {
  const { activated, setActivated } = useActivate();
  const [errorMessage, setErrorMessage] = useState("");
  const [loading, setLoading] = useState(false);
  const { updateNotice } = useNotification();

  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      email: WCF_ADDONS_ADMIN.addons_config.sl_lic_email || "",
      license: WCF_ADDONS_ADMIN.addons_config.sl_lic || "",
    },
  });

  const onSubmit = async (data) => {
    setLoading(true);
    setErrorMessage("");
    const body_args = {
      action: activated?.wcf_valid
        ? "wcf_addon_pro_sl_deactivate"
        : "wcf_addon_pro_sl_activate",
      wcf_addon_sl_license_key: data.license,
      email: data.email,
      nonce: WCF_ADDONS_ADMIN.nonce,
    };

    if (activated?.wcf_valid) {
      body_args["edd_license_deactivate"] = true;
    }

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams(body_args),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        if (return_content.success) {
          const date = new Date();
          const utcDate = date.toISOString();

          if (return_content?.license === "valid") {
            WCF_ADDONS_ADMIN.addons_config.wcf_valid = true;
            setActivated(WCF_ADDONS_ADMIN.addons_config);
            toast.success("Activate Successful", {
              position: "top-right",
            });

            const sampleData = {
              type: "notice",
              title: "License activation update",
              description:
                "Your license has been successfully activated. You can now enjoy all the features of the plugin. Thank you for choosing our plugin!",
              date: utcDate,
            };

            updateNotice(sampleData);
          } else {
            WCF_ADDONS_ADMIN.addons_config.wcf_valid = false;
            setActivated(WCF_ADDONS_ADMIN.addons_config);
            toast.success("Deactivate Successful", {
              position: "top-right",
            });

            const sampleData = {
              type: "notice",
              title: "License Deactivation update",
              description:
                "Your license has been successfully deactivated. Thank you for using our plugin!",
              date: utcDate,
            };

            updateNotice(sampleData);
          }
        } else {
          setErrorMessage(return_content.message);
        }
        setLoading(false);
      });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent
        onOpenAutoFocus={(e) => e.preventDefault()}
        className="w-[498px] max-w-[498px] rounded-xl bg-background pr-0 [&>.wcf-dialog-close-button>svg]:text-[#99A0AE] [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4 p-6 gap-0"
      >
        <DialogHeader>
          <DialogTitle className="text-2xl font-medium">
            Activate License
          </DialogTitle>
          <DialogDescription className="text-base mt-[9px]">
            Enter your license key to activate Animation Addons Pro. If you need
            guidance please go to the instructions guideline for help.
          </DialogDescription>
        </DialogHeader>
        <Separator className="my-6 bg-[#EAECF0]" />
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <FormField
              control={form.control}
              name="email"
              render={({ field }) => (
                <FormItem>
                  <div className="flex gap-1 items-center">
                    <FormLabel className="font-normal text-text">
                      Your email
                    </FormLabel>
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger className="bg-transparent flex items-center">
                          <RiInformation2Fill color="#CACFD8" size={18} />
                        </TooltipTrigger>
                        <TooltipContent className="max-w-[200px]">
                          <p>
                            Enter the email you've provided for your pro license
                          </p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                  <FormControl>
                    <Input placeholder="Enter your email here" {...field} />
                  </FormControl>

                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="license"
              render={({ field }) => (
                <FormItem className="mt-5">
                  <div className="flex gap-1 items-center">
                    <FormLabel className="font-normal text-text">
                      License Key
                    </FormLabel>
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger className="bg-transparent flex items-center">
                          <RiInformation2Fill color="#CACFD8" size={18} />
                        </TooltipTrigger>
                        <TooltipContent className="max-w-[200px]">
                          <p>
                            Copy the license key given in your downloaded file
                            and paste in below
                          </p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                  <FormControl className="relative">
                    <Input
                      placeholder="Enter your license key here"
                      {...field}
                    />
                  </FormControl>

                  <FormMessage />
                </FormItem>
              )}
            />

            {errorMessage ? (
              <div className="mt-4">
                <p className="text-[0.8rem] font-medium text-[#FF5733] leading-4">
                  {errorMessage}
                </p>
              </div>
            ) : (
              ""
            )}

            <Separator className="my-6 bg-[#EAECF0]" />
            <Button type="submit" variant="pro" className="w-full gap-2">
              {loading ? <Loader2 className="animate-spin" /> : ""}
              {activated?.wcf_valid
                ? "Deactivate your license"
                : "Activate your license"}
            </Button>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
};

export default LicenseDialog;
