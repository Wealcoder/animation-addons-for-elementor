import {
  RiApps2AddLine,
  RiCommandLine,
  RiLayoutGridLine,
  RiPuzzle2Line,
  RiShareBoxLine,
  RiVipCrown2Line,
} from "react-icons/ri";

export const MainNavData = [
  {
    name: "Dashboard",
    path: "dashboard",
    role: ["administrator", "editor"],
    icon: <RiLayoutGridLine size={20} />,
  },
  {
    name: "Widgets",
    path: "widgets",
    role: ["administrator", "editor"],
    icon: <RiCommandLine size={20} />,
  },
  {
    name: "Extensions",
    path: "extensions",
    role: ["administrator", "editor"],
    icon: <RiApps2AddLine size={20} />,
  },
  {
    name: "Free vs Pro",
    path: "free-pro",
    role: ["administrator"],
    icon: <RiVipCrown2Line size={20} />,
  },
  {
    name: "Integrations",
    path: "integrations",
    role: ["administrator"],
    icon: <RiPuzzle2Line size={20} />,
  },
  {
    name: "Starter Template",
    path: "stater-template",
    icon: <RiShareBoxLine size={20} />,
    targetBlank: true,
  },
];
