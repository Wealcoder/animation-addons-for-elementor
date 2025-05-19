import { buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";

const TikTokSettings = () => {
  console.log(WCF_ADDONS_ADMIN.adminURL);
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
          <Input id="access-token" disabled placeholder="Access Token" />
        </div>
        <div className="grid w-full max-w-sm items-center gap-1.5">
          <Label htmlFor="refresh-token">Refresh Token</Label>
          <Input id="refresh-token" disabled placeholder="Refresh Token" />
        </div>
      </div>
    </div>
  );
};

export default TikTokSettings;
