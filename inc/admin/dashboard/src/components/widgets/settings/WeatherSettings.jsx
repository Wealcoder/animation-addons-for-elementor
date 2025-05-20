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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

// Schema
const FormSchema = z.object({
  api_key: z.string().min(1, "API key is required"),
  country_name: z.string().min(1, "Location is required"),
  unit: z.string(),
});

const WeatherSettings = () => {
  const dialogCloseRef = useRef(null);
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      api_key: "",
      country_name: "",
      unit: "metric",
    },
  });

  const { watch, setError, clearErrors, reset } = form;
  const apiKey = watch("api_key");
  const countryName = watch("country_name");

  // State to track validation status
  const [validationStatus, setValidationStatus] = useState({
    api_key: null, // null: not validated, true: valid, false: invalid
    country_name: null,
  });

  // ✅ API validation logic
  const validateApiKey = async (key) => {
    if (!key) {
      setValidationStatus((prev) => ({ ...prev, api_key: null }));
      return;
    }
    try {
      const res = await fetch(
        `https://api.openweathermap.org/data/2.5/weather?appid=${key}`
      );

      const data = await res.json();

      if (data.cod != 400) {
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
  const validateCountryName = async (countryName, apiKey) => {
    if (!countryName) {
      setValidationStatus((prev) => ({ ...prev, country_name: null }));
      return;
    }
    if (!apiKey) {
      setError("country_name", {
        type: "manual",
        message: "Please enter a valid API key",
      });
      setValidationStatus((prev) => ({ ...prev, country_name: false }));
      return;
    }
    try {
      const res = await fetch(
        `https://api.openweathermap.org/data/2.5/weather?q=${countryName}&appid=${apiKey}`
      );

      const data = await res.json();

      if (data.cod != 200) {
        setError("country_name", {
          type: "manual",
          message: "Invalid Location or not accessible with this API key",
        });
        setValidationStatus((prev) => ({ ...prev, country_name: false }));
      } else {
        clearErrors("country_name");
        setValidationStatus((prev) => ({ ...prev, country_name: true }));
      }
    } catch (err) {
      setError("country_name", {
        type: "manual",
        message: "Location validation failed",
      });
      setValidationStatus((prev) => ({ ...prev, country_name: false }));
    }
  };

  // ✅ Debounce wrapped validators
  const debouncedValidateApiKey = useMemo(
    () => debounceFn((key) => validateApiKey(key), 500),
    []
  );

  const debouncedValidateCountryName = useMemo(
    () =>
      debounceFn(
        (countryName, key) => validateCountryName(countryName, key),
        500
      ),
    []
  );

  // ✅ Call on api_key change
  useEffect(() => {
    if (apiKey) {
      debouncedValidateApiKey(apiKey);
      // If playlist ID exists, validate it too when API key changes
      if (countryName) {
        debouncedValidateCountryName(countryName, apiKey);
      }
    } else {
      setValidationStatus((prev) => ({ ...prev, api_key: null }));
    }
  }, [apiKey]);

  // ✅ Call on country_name change
  useEffect(() => {
    if (countryName) {
      debouncedValidateCountryName(countryName, apiKey);
    } else {
      setValidationStatus((prev) => ({ ...prev, country_name: null }));
    }
  }, [countryName]);

  const getFullData = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        action: "aae_get_dynamic_settings",
        setting_name: "aae_weather_api_advanced_settings",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        reset({
          api_key: return_content.settings.api_key || "",
          unit: return_content.settings.unit || "",
          country_name: return_content.settings.country_name || "",
        });
      });
  };

  useEffect(() => {
    getFullData();
  }, []);

  async function onSubmit(data) {
    const apiKeyError = form.getFieldState("api_key").error;
    const CountryNameError = form.getFieldState("country_name").error;

    if (apiKeyError || CountryNameError) return;

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        action: "aae_save_dynamic_settings",
        setting_name: "aae_weather_api_advanced_settings",
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
        <h2 className="text-xl text-text font-medium">Weather</h2>
        <p className="text-sm text-text-secondary mt-1">
          Add credentials to your Weather
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
                name="country_name"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <FormLabel className="text-[#0E121B]">Location</FormLabel>
                    <div className="relative">
                      <FormControl>
                        <Input
                          placeholder="Enter your country/city name"
                          {...field}
                        />
                      </FormControl>
                      {validationStatus.country_name !== null && (
                        <span className="absolute right-3 top-1/2 transform -translate-y-1/2 flex">
                          {validationStatus.country_name ? (
                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                          ) : (
                            <XCircle className="h-5 w-5 text-red-500" />
                          )}
                        </span>
                      )}
                    </div>
                    <FormDescription>
                      Add your Location to get the weather information.
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="unit"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <FormLabel className="text-[#0E121B]">Unit</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select a verified email to display" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="standard">Standard</SelectItem>
                        <SelectItem value="metric">Metric</SelectItem>
                        <SelectItem value="imperial">Imperial</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormDescription>
                      Select the unit of measurement for temperature.
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

export default WeatherSettings;
