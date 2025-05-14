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
import { useRef, useEffect, useMemo } from "react";
import { Input } from "@/components/ui/input";
import { debounceFn } from "@/lib/utils";

// Schema
const FormSchema = z.object({
  api_key: z.string().min(1, "API key is required"),
  username: z.string().optional(),
  playlist_id: z.string().optional(),
});

const YoutubeVideoSettings = () => {
  const dialogCloseRef = useRef(null);
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      api_key: "",
      username: "",
      playlist_id: "",
    },
  });

  const { watch, setError, clearErrors, reset } = form;
  const apiKey = watch("api_key");

  // ✅ API validation logic
  const validateApiKey = async (key) => {
    if (!key) return;
    try {
      const res = await fetch(
        `${WCF_ADDONS_ADMIN.home_url}wp-json/aae-addon-yt/v1/status?key=${key}`,
        {
          method: "GET",
          headers: { "Content-Type": "application/json" },
        }
      );

      const data = await res.json();

      if (data.status !== "ok") {
        setError("api_key", {
          type: "manual",
          message: "Invalid API key",
        });
      } else {
        clearErrors("api_key");
      }
    } catch (err) {
      setError("api_key", {
        type: "manual",
        message: "API validation failed",
      });
    }
  };

  // ✅ Debounce wrapped validator
  const debouncedValidateApiKey = useMemo(
    () => debounceFn((key) => validateApiKey(key), 500),
    []
  );

  // ✅ Call on api_key change
  useEffect(() => {
    if (apiKey) {
      debouncedValidateApiKey(apiKey);
    }
  }, [apiKey]);

  const getFullData = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "aae_get_dynamic_settings",
        setting_name: "aae_youtube_video_advanced_settings",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        reset({
          api_key: return_content.settings.api_key || "",
          username: return_content.settings.username || "",
          playlist_id: return_content.settings.playlist_id || "",
        });
      });
  };

  useEffect(() => {
    getFullData();
  }, []);

  async function onSubmit(data) {
    const error = form.getFieldState("api_key").error;
    if (error) return;

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "aae_save_dynamic_settings",
        setting_name: "aae_youtube_video_advanced_settings",
        form_fields: JSON.stringify(data),
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
        <h2 className="text-xl text-text font-medium">Youtube Video</h2>
        <p className="text-sm text-text-secondary mt-1">
          Add credentials to your Youtube video
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
                    <FormControl>
                      <Input placeholder="Enter your API key" {...field} />
                    </FormControl>
                    <FormDescription>
                      This key will be validated with the API.
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="username"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <FormLabel className="text-[#0E121B]">User Name</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter your username" {...field} />
                    </FormControl>
                    <FormDescription>
                      The font will be loaded in the head section
                    </FormDescription>
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="playlist_id"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <FormLabel className="text-[#0E121B]">
                      Playlist Id
                    </FormLabel>
                    <FormControl>
                      <Input placeholder="Enter your playlist id" {...field} />
                    </FormControl>
                    <FormDescription>
                      The font will be loaded in the head section
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

export default YoutubeVideoSettings;
