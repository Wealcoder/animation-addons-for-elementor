import { useForm } from "react-hook-form";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import { DesktopIcon } from "@radix-ui/react-icons";
import logo from "../../../../../../public/images/extensions/scroll_smother.png";

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
import { deviceList } from "@//config/data/deviceList";
import { Badge } from "@/components/ui/badge";
import {
  LaptopIcon,
  MonitorIcon,
  SmartphoneIcon,
  TabletIcon,
} from "lucide-react";

const defaultValues = {
  desktop: {
    enabled: true,
    smotherLevel: "1.35",
  },
  laptop: {
    enabled: false,
    smotherLevel: "1.35",
  },
  tablet: {
    enabled: false,
    smotherLevel: "1.35",
  },
  mobile: {
    enabled: false,
    smotherLevel: "1.35",
  },
};

const deviceSchema = z.object({
  enabled: z.boolean(),
  // smotherLevel: z.number().optional(),
  // smotherLevel: z.string().optional(),

  smotherLevel: z.coerce
    .number({
      invalid_type_error: "Smooth must be a number",
    })
    .optional(),
});

const FormSchema = z.object({
  desktop: deviceSchema,
  laptop: deviceSchema,
  tablet: deviceSchema,
  mobile: deviceSchema,
});

// const FormSchema = z.object({
//   smooth: z.coerce
//     .number({
//       invalid_type_error: "Smooth must be a number",
//     })
//     .optional(),
//   mobile: z.boolean().optional(),
//   disableMode: z.boolean().optional(),
//   media: z.string().regex(/^(?:\d+px|min-width:\s?\d+px|max-width:\s?\d+px)$/, {
//     message:
//       "Invalid format. Use '900px', 'min-width: 800px', or 'max-width: 1024px'.",
//   }),
// });

const ScrollSmootherSettings = () => {
  const dialogCloseRef = useRef(null);
  const [tabValue, setTabValue] = useState(deviceList[0].id);

  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      desktop: {
        ...defaultValues.desktop,
        ...WCF_ADDONS_ADMIN?.smoothScroller?.desktop,
      },
      laptop: {
        ...defaultValues.laptop,
        ...WCF_ADDONS_ADMIN?.smoothScroller?.laptop,
      },
      tablet: {
        ...defaultValues.tablet,
        ...WCF_ADDONS_ADMIN?.smoothScroller?.tablet,
      },
      mobile: {
        ...defaultValues.mobile,
        ...WCF_ADDONS_ADMIN?.smoothScroller?.mobile,
      },
    },
  });

  const convertToMinWidth = (value) => {
    if (/^\d+px$/.test(value)) {
      return `min-width: ${value}`;
    }
    return value;
  };

  async function onSubmit(formData) {
    // const convertedMedia = convertToMinWidth(data.media);

    // console.log(formData);
    // return null;

    if (!WCF_ADDONS_ADMIN.nonce || !WCF_ADDONS_ADMIN.ajaxurl) return null;

    const payload = {
      desktop: formData.desktop,
      laptop: formData.laptop,
      tablet: formData.tablet,
      mobile: formData.mobile,
      nonce: WCF_ADDONS_ADMIN.nonce ?? null,
    };

    await fetch(
      // WCF_ADDONS_ADMIN.ajaxurl,
      `${WCF_ADDONS_ADMIN.ajaxurl}?action=save_smooth_scroller_settings`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
        credentials: "same-origin",

        // body: new URLSearchParams({
        //   action: "save_smooth_scroller_settings",
        //   mobile: data.mobile,
        //   disableMode: data.disableMode,
        //   smooth: data.smooth,
        //   media: convertedMedia,
        //   nonce: WCF_ADDONS_ADMIN.nonce,
        // }),
      }
    )
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        WCF_ADDONS_ADMIN.smoothScroller = JSON.parse(return_content);
        if (dialogCloseRef.current) {
          dialogCloseRef.current.click();
        }

        toast.success("Save Successful", {
          position: "top-right",
        });
      });
  }

  const resetHandler = async () => {
    await onSubmit(defaultValues);
    form.reset({ ...defaultValues });
  };

  // console.log(tabValue);
  // console.log(tabValue);

  return (
    <div className="py-4 px-6 pb-7">
      <div className="flex items-center gap-2 mt-2">
        <img
          // src={`${WCF_ADDONS_ADMIN.root_url}public/images/extensions/scroll_smother.png`}
          src={logo}
          alt="logo"
          className="w-[65px] h-[65px]"
        />

        <div>
          <h2 className=" flex items-center gap-2">
            <span className="text-[20px] font-medium text-[var(--900,#181B25)]">
              {" "}
              Scroll Smother
            </span>
            <Badge
              className="bg-[linear-gradient(109deg,#ffab472e_0%,#ffab472e_100%)] text-[#717784]"
              variant="pro"
            >
              PRO
            </Badge>
          </h2>
          <p className="text-sm text-text-secondary mt-2">
            Enter Smooth Scroller value below
          </p>
        </div>
      </div>

      <div className="mt-7">
        <Tabs value={tabValue} onValueChange={setTabValue}>
          <div className="flex justify-between items-center">
            <TabsList className="gap-1 h-11">
              {deviceList.map((device) => {
                const TabIcon =
                  device.id === "desktop"
                    ? MonitorIcon
                    : device.id === "laptop"
                    ? LaptopIcon
                    : device.id === "tablet"
                    ? TabletIcon
                    : SmartphoneIcon;

                return (
                  <TabsTrigger
                    key={device.id}
                    value={device.id}
                    className="data-[state=active]:bg-[#E1E4EA] bg-[#F5F7FA]"
                    sx={{ boxShadow: "none" }}
                  >
                    <TabIcon
                      size={16}
                      color={tabValue === device.id ? "#181B25" : "#525866"}
                    />

                    <span
                      style={{
                        color: tabValue === device.id ? "#181B25" : "#525866",
                      }}
                      className="text-[12px] ml-1"
                    >
                      {device.label}
                    </span>
                  </TabsTrigger>
                );
              })}
            </TabsList>
          </div>

          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)}>
              {deviceList.map((device) => (
                <TabsContent
                  value={device.id}
                  className="bg-background p-4 rounded-lg mt-0"
                >
                  <div className="mt-3">
                    <FormField
                      control={form.control}
                      name={`${device.id}.enabled`}
                      render={({ field }) => (
                        <FormItem className="flex flex-row items-center gap-3">
                          <FormLabel className="min-w-[135px]">
                            {" "}
                            Enable On {device.label}
                          </FormLabel>
                          <FormControl>
                            <Switch
                              checked={field.value}
                              onCheckedChange={field.onChange}
                              sx={{ marginTop: "0" }}
                            />
                          </FormControl>
                        </FormItem>
                      )}
                    />
                  </div>

                  <div className="mt-5 max-w-[180px]">
                    <FormField
                      control={form.control}
                      name={`${device.id}.smotherLevel`}
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="text-[12px] text-[var(--600,#525866)] mb-2">
                            Set the scroll smother level
                          </FormLabel>
                          <FormControl>
                            <Input
                              type="number"
                              step="0.01"
                              {...field}
                              value={field.value ?? ""}
                              onChange={(e) => {
                                const value = e.target.value;

                                field.onChange(
                                  value !== "" ? parseFloat(value) : ""
                                );

                                // field.onChange(
                                //   value === "" ? undefined : Number(value)
                                // );
                              }}
                            />
                          </FormControl>
                        </FormItem>
                      )}
                    />
                  </div>
                </TabsContent>
              ))}

              <div className="flex gap-2.5 items-center mt-9">
                <Button
                  className="p-[20px] rounded-[8px]"
                  variant="secondary"
                  onClick={resetHandler}
                >
                  Reset
                </Button>
                <Button className="p-[20px] rounded-[8px]" type="submit">
                  {" "}
                  Save Settings{" "}
                </Button>
              </div>
            </form>
          </Form>
        </Tabs>
      </div>
    </div>
  );
};

export default ScrollSmootherSettings;
