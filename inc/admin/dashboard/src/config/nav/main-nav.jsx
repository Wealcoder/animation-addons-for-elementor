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
    icon: <RiLayoutGridLine size={20} />,
  },
  {
    name: "Widgets",
    path: "widgets",
    icon: <RiCommandLine size={20} />,
  },
  {
    name: "Extensions",
    path: "extensions",
    icon: <RiApps2AddLine size={20} />,
  },
  {
    name: "Free vs Pro",
    path: "free-pro",
    icon: <RiVipCrown2Line size={20} />,
  },
  {
    name: "Integrations",
    path: "integrations",
    icon: <RiPuzzle2Line size={20} />,
  },
  // {
  //   name: "Starter Template",
  //   path: "stater-template",
  //   icon: <RiShareBoxLine size={20} />,
  //   targetBlank: true,
  // },
];
