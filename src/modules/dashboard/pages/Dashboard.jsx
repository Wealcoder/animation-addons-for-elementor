import AddonProWidget from "@/components/dashboard/AddonProWidget";
import ConnectWithUs from "@/components/dashboard/ConnectWithUs";
import Documentation from "@/components/dashboard/Documentation";
import LatestBlog from "@/components/dashboard/LatestBlog";
import RecoPlugins from "@/components/dashboard/RecoPlugins";
import Tutorial from "@/components/dashboard/Tutorial";
import { Badge } from "@/components/ui/badge";
import HeroBanner from "../../../../public/images/dashboard-hero-banner.png";
import QuickAccess from "@/components/dashboard/QuickAccess";
import { cn } from "@/lib/utils";
import { buttonVariants } from "../components/ui/button";

const Dashboard = () => {
  return (
    <div className="flex flex-col gap-6">
      {WCF_ADDONS_ADMIN.hero !== "no" ? (
        <div className="relative">
          <img
            src={WCF_ADDONS_ADMIN.hero}
            className="w-full h-full rounded-[10px]"
            alt="Banner"
          />
          <Badge
            className="absolute bottom-[34px] right-[20px]"
            variant="version"
          >
            Ver. {WCF_ADDONS_ADMIN?.version}
          </Badge>
        </div>
      ) : (
        <div className="relative">
          <video
            src={
              window.innerWidth < 768
                ? WCF_ADDONS_ADMIN.video_link.mobile
                : WCF_ADDONS_ADMIN.video_link.desktop
            }
            className="w-full h-full rounded-[10px]"
            poster={HeroBanner}
            autoPlay
            loop
            muted
            playsInline
            controls={false}
          />
          <Badge
            className="absolute bottom-[10px] left-[6px]"
            variant="version"
          >
            Ver. {WCF_ADDONS_ADMIN?.version}
          </Badge>
          <a
            href="https://animation-addons.com/pricing"
            target="_blank"
            className={cn(
              buttonVariants(),
              "absolute top-[30px] xl:top-[50px] right-[40px] xl:right-[55px] px-[15px] xl:px-[25px] bg-[#ff4401] gap-2"
            )}
          >
            <svg
              width="18"
              height="18"
              viewBox="0 0 512 512"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <g clip-path="url(#clip0_96_1878)">
                <path
                  d="M306 4.00001C306.775 4.17129 307.551 4.34258 308.35 4.51905C328.442 9.03849 347.832 17.6992 365 29C365.605 29.3966 366.209 29.7931 366.832 30.2017C408.343 57.8193 437.164 100.743 447.527 149.48C450.199 163.17 451.128 176.791 451.211 190.73C451.23 192.711 451.249 194.691 451.269 196.672C451.283 198.051 451.297 199.43 451.31 200.81C451.952 267.397 461.547 330.329 475.127 395.365C475.551 397.398 475.975 399.431 476.398 401.465C476.92 403.97 477.444 406.475 477.97 408.98C483.077 433.514 484.234 455.439 470.688 477.688C458.371 496.109 441.173 506.228 419.688 510.688C401.789 512.204 383.55 509.83 368.816 498.82C365.434 497.293 362.377 498.878 359.004 499.973C340.604 505.8 320.218 504.847 302.91 496.109C297.326 493.146 291.988 489.893 287 486C286.113 486.969 285.226 487.939 284.312 488.938C275.714 497.484 265.257 503.658 254 508C252.931 508.414 251.863 508.828 250.762 509.254C230.74 515.762 208.435 513.327 189.75 504.25C179.191 498.835 168.681 492.022 162 482C158.644 483.119 158.199 483.791 156.125 486.5C143.343 501.452 124.379 507.441 105.414 509.246C103.236 509.315 101.17 509.205 99 509C97.9816 508.919 96.9633 508.838 95.9141 508.754C81.9284 507.23 69.3466 502.318 58 494C57.3813 493.564 56.7625 493.129 56.125 492.68C42.1327 481.862 33.2509 463.696 30.4453 446.625C30.0041 443.034 29.7972 439.555 29.75 435.938C29.7242 434.771 29.6984 433.604 29.6719 432.402C30.1608 420.974 34.1389 409.986 37.8402 399.277C39.2048 395.318 40.5349 391.348 41.8672 387.379C42.1354 386.581 42.4037 385.782 42.6801 384.96C63.3611 323.257 72.9811 259.438 74.3362 194.493C75.4194 142.605 90.2624 95.5086 126.635 57.4321C127.949 56.053 129.247 54.6577 130.543 53.2617C174.477 7.3342 244.806 -10.3866 306 4.00001ZM311.188 130.125C300.388 142.043 298.148 159.224 298.825 174.666C300.315 194.557 307.471 214.463 322.102 228.465C329.383 234.353 336.685 236.944 346 236C353.09 234.096 357.917 228.876 362 223C371.165 205.551 371.547 184.474 366.199 165.734C361.299 150.476 353.533 134.115 338.98 125.895C329.079 121.422 319.236 122.659 311.188 130.125ZM188.645 133.09C172.192 150.109 167.43 173.22 167.723 196.133C168.115 208.296 171.448 220.101 179.988 229.094C185.833 234.465 190.411 236.525 198.391 236.301C208.399 235.147 215.601 228.089 221.941 220.754C235.342 202.976 240.527 179.064 237.469 157.203C236.775 153.751 235.957 150.388 235 147C234.745 146.095 234.49 145.19 234.227 144.258C231.547 136.367 226.226 129.381 218.922 125.266C207.58 120.353 197.297 125.413 188.645 133.09ZM258.094 237.664C250.713 245.514 249.522 253.247 249.573 263.682C249.835 271.784 252.045 279.215 258 285C261.451 287.614 264.244 288.894 268.562 289.5C274.828 288.589 278.838 284.748 282.676 279.953C287.999 272.102 288.185 262.167 287 253C285.05 245.263 281.899 239.24 275 235C268.674 231.837 263.335 233.089 258.094 237.664Z"
                  fill="white"
                />
              </g>
              <defs>
                <clipPath id="clip0_96_1878">
                  <rect width="512" height="512" fill="white" />
                </clipPath>
              </defs>
            </svg>{" "}
            Buy Now
          </a>
        </div>
      )}

      <div className="mt-2">
        <QuickAccess />
      </div>
      <div className="grid grid-cols-2 xl:grid-cols-3 gap-6 h-full">
        <Tutorial />
        <Documentation />
      </div>
      <div className="grid grid-cols-2 xl:grid-cols-3 gap-6 h-full">
        <AddonProWidget />
        <RecoPlugins />
      </div>
      <ConnectWithUs />
      <LatestBlog />
    </div>
  );
};

export default Dashboard;
