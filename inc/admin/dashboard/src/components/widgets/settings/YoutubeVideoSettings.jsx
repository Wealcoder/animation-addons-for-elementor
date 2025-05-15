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
import { useRef, useEffect, useMemo, useState } from "react";
import { Input } from "@/components/ui/input";
import { debounceFn } from "@/lib/utils";
import { CheckCircle2, XCircle } from "lucide-react";

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
  const playlistId = watch("playlist_id");

  // State to track validation status
  const [validationStatus, setValidationStatus] = useState({
    api_key: null, // null: not validated, true: valid, false: invalid
    playlist_id: null,
  });

  // ✅ API validation logic
  const validateApiKey = async (key) => {
    if (!key) {
      setValidationStatus((prev) => ({ ...prev, api_key: null }));
      return;
    }
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
        setValidationStatus((prev) => ({ ...prev, api_key: false }));
      } else {
        clearErrors("api_key");
        setValidationStatus((prev) => ({ ...prev, api_key: true }));
      }
    } catch (err) {
      setError("api_key", {
        type: "manual",
        message: "API validation failed",
      });
      setValidationStatus((prev) => ({ ...prev, api_key: false }));
    }
  };

  // ✅ Playlist validation logic
  const validatePlaylistId = async (id, apiKey) => {
    if (!id) {
      setValidationStatus((prev) => ({ ...prev, playlist_id: null }));
      return;
    }
    if (!apiKey) {
      setError("playlist_id", {
        type: "manual",
        message: "Please enter a valid API key first",
      });
      setValidationStatus((prev) => ({ ...prev, playlist_id: false }));
      return;
    }
    try {
      const res = await fetch(
        `${WCF_ADDONS_ADMIN.home_url}wp-json/aae-addon-yt/v1/playlist-status?key=${apiKey}&playlistId=${id}`,
        {
          method: "GET",
          headers: { "Content-Type": "application/json" },
        }
      );

      const data = await res.json();

      if (!data.status?.privacyStatus) {
        setError("playlist_id", {
          type: "manual",
          message: "Invalid Playlist ID or not accessible with this API key",
        });
        setValidationStatus((prev) => ({ ...prev, playlist_id: false }));
      } else {
        clearErrors("playlist_id");
        setValidationStatus((prev) => ({ ...prev, playlist_id: true }));
      }
    } catch (err) {
      setError("playlist_id", {
        type: "manual",
        message: "Playlist validation failed",
      });
      setValidationStatus((prev) => ({ ...prev, playlist_id: false }));
    }
  };

  // ✅ Debounce wrapped validators
  const debouncedValidateApiKey = useMemo(
    () => debounceFn((key) => validateApiKey(key), 500),
    []
  );

  const debouncedValidatePlaylistId = useMemo(
    () => debounceFn((id, key) => validatePlaylistId(id, key), 500),
    []
  );

  // ✅ Call on api_key change
  useEffect(() => {
    if (apiKey) {
      debouncedValidateApiKey(apiKey);
      // If playlist ID exists, validate it too when API key changes
      if (playlistId) {
        debouncedValidatePlaylistId(playlistId, apiKey);
      }
    } else {
      setValidationStatus((prev) => ({ ...prev, api_key: null }));
    }
  }, [apiKey]);

  // ✅ Call on playlist_id change
  useEffect(() => {
    if (playlistId) {
      debouncedValidatePlaylistId(playlistId, apiKey);
    } else {
      setValidationStatus((prev) => ({ ...prev, playlist_id: null }));
    }
  }, [playlistId]);

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
    const apiKeyError = form.getFieldState("api_key").error;
    const playlistIdError = form.getFieldState("playlist_id").error;

    if (apiKeyError || playlistIdError) return;

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
                    <div className="relative">
                      <FormControl>
                        <Input placeholder="Enter your API key" {...field} />
                      </FormControl>
                      {validationStatus.api_key !== null && (
                        <span className="absolute right-3 top-1/2 transform -translate-y-1/2 flex">
                          {validationStatus.api_key ? (
                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                          ) : (
                            <XCircle className="h-5 w-5 text-red-500" />
                          )}
                        </span>
                      )}
                    </div>
                    <FormDescription>
                      This key will be validated.
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
                      Add your Youtube username to get videos from your channel.
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
                    <div className="relative">
                      <FormControl>
                        <Input
                          placeholder="Enter your playlist id"
                          {...field}
                        />
                      </FormControl>
                      {validationStatus.playlist_id !== null && (
                        <span className="absolute right-3 top-1/2 transform -translate-y-1/2 flex">
                          {validationStatus.playlist_id ? (
                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                          ) : (
                            <XCircle className="h-5 w-5 text-red-500" />
                          )}
                        </span>
                      )}
                    </div>
                    <FormDescription>
                      Add your Youtube playlist id to get videos from your
                      playlist.
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
