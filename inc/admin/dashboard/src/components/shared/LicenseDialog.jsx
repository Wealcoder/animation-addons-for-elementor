import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
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
import { RiInformation2Fill, RiKey2Line } from "react-icons/ri";
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
import { useState } from "react";

const FormSchema = z.object({
  email: z.string().email(),
  license: z.string().min(1, {
    message: "Please enter your license",
  }),
});

const LicenseDialog = () => {
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      email: "",
      license: "",
    },
  });

  const onSubmit = (data) => {
    console.log(data);
  };

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button variant="pro">
          <span className="me-1.5 flex">
            <RiKey2Line size={20} />
          </span>
          Activate License
        </Button>
      </DialogTrigger>
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

            <Separator className="my-6 bg-[#EAECF0]" />
            <Button type="submit" variant="pro" className="w-full">
              Activate your licence
            </Button>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
};

export default LicenseDialog;
