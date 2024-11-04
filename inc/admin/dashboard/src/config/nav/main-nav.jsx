import {
  RiApps2AddLine,
  RiCommandLine,
  RiLayoutGridLine,
  RiShareBoxLine,
  RiVipCrown2Line,
} from "react-icons/ri";

export const MainNavData = [
  {
    name: "Dashboard",
    path: "http://localhost:10084/wp-admin/admin.php?page=wcf_addons_settings",
    icon: <RiLayoutGridLine size={20} />,
  },
  {
    name: "Widgets",
    path: "/widgets",
    icon: <RiCommandLine size={20} />,
  },
  {
    name: "Extensions",
    path: "/extensions",
    icon: <RiApps2AddLine size={20} />,
  },
  {
    name: "Free vs Pro",
    path: "/free-pro",
    icon: <RiVipCrown2Line size={20} />,
  },
  {
    name: "Starter Template",
    path: "/stater-template",
    icon: <RiShareBoxLine size={20} />,
    targetBlank: true,
  },
];
