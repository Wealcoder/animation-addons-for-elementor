import TikTokSettings from "@/components/widgets/settings/TikTokSettings";
import WeatherSettings from "@/components/widgets/settings/WeatherSettings";
import YoutubeVideoSettings from "@/components/widgets/settings/YoutubeVideoSettings";
import MailchimpSettings from "../components/widgets/settings/MailchimpSettings";
import LoopGridSettings from "./../components/widgets/settings/LoopGridSettings";

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
  {
    key: "mailchimp",
    component: <MailchimpSettings />,
  },
  {
    key: "advanced-mailchimp",
    component: <MailchimpSettings />,
  },
  {
    key: "aae-a-loop-grid",
    component: <LoopGridSettings />,
  },
];
