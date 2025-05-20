import TikTokSettings from "@/components/widgets/settings/TikTokSettings";
import WeatherSettings from "@/components/widgets/settings/WeatherSettings";
import YoutubeVideoSettings from "@/components/widgets/settings/YoutubeVideoSettings";

export const WidgetSettingConfig = [
  {
    key: "youtube-video",
    component: <YoutubeVideoSettings />,
  },
  {
    key: "weather",
    component: <WeatherSettings />,
  },
  {
    key: "tiktok-feed",
    component: <TikTokSettings />,
  },
];
