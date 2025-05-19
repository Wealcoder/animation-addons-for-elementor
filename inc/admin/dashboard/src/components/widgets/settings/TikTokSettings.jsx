import { buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";
import { useEffect, useState } from "react";

const TikTokSettings = () => {
  const [token, setToken] = useState({
    accessToken: "",
    refreshToken: "",
  });
  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const accessToken = urlParams.get("tiktok_access_token");
    const refreshToken = urlParams.get("tiktok_refresh_token");

    if (accessToken && refreshToken) {
      const tokenValue = {
        accessToken: accessToken || "",
        refreshToken: refreshToken || "",
      };
      setToken(tokenValue);
      onSubmit(tokenValue);
      urlParams.delete("tiktok_access_token");
      urlParams.delete("tiktok_refresh_token");
      urlParams.delete("wiz_setting");

      const baseUrl = `${window.location.origin}${window.location.pathname}`;
      const remainingParams = urlParams.toString();
      const hash = window.location.hash;

      const cleanedUrl = `${baseUrl}${
        remainingParams ? `?${remainingParams}` : ""
      }${hash}`;
      window.history.replaceState({}, document.title, cleanedUrl);
    } else {
      getFullData();
    }
  }, []);

  const getFullData = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        action: "aae_get_dynamic_settings",
        setting_name: "aae_tiktok_api_advanced_settings",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        setToken({
          accessToken: return_content.settings.accessToken || "",
          refreshToken: return_content.settings.refreshToken || "",
        });
      });
  };

  async function onSubmit(data) {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        action: "aae_save_dynamic_settings",
        setting_name: "aae_tiktok_api_advanced_settings",
        form_fields: JSON.stringify(data),
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {});
  }

  return (
    <div className="py-5">
      <div className="px-6 pb-4 border-b border-[#F2F5F8]">
        <h2 className="text-xl text-text font-medium">TikTok Feed</h2>
        <p className="text-sm text-text-secondary mt-1">
          Add credentials to your TikTok Feed
        </p>
      </div>
      <div className="px-6 py-5 border-b border-[#F2F5F8]">
        <a
          href={`https://tiktok-feed.animation-addons.com/callback?redirect_uri=${WCF_ADDONS_ADMIN.adminURL}/admin.php&page=wcf_addons_settings&tab=widgets&cTab=all#tiktok-feed`}
          target="_blank"
          rel="noreferrer"
          className={cn(buttonVariants(), "w-full no-underline")}
        >
          Add TikTok Account
        </a>
      </div>
      <div className="px-6 py-5 space-y-4">
        <div className="grid w-full max-w-sm items-center gap-1.5">
          <Label htmlFor="access-token">Access Token</Label>
          <Input
            id="access-token"
            disabled
            placeholder="Access Token"
            value={token.accessToken}
          />
        </div>
        <div className="grid w-full max-w-sm items-center gap-1.5">
          <Label htmlFor="refresh-token">Refresh Token</Label>
          <Input
            id="refresh-token"
            disabled
            placeholder="Refresh Token"
            value={token.refreshToken}
          />
        </div>
      </div>
    </div>
  );
};

export default TikTokSettings;
