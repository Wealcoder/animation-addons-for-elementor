import {
  RiCustomerServiceLine,
  RiFileTextLine,
  RiGroup3Line,
  RiNewsLine,
  RiPuzzle2Line,
  RiStarLine,
} from "react-icons/ri";

export const DashboardSearchContent = [
  {
    name: "Documentation",
    slug: "wcf-documentation",
    dIcon: <RiFileTextLine size={20} color="#4870FF" />,
  },
  {
    name: "Recommended Plugins",
    slug: "wcf-recommended-plugins",
    dIcon: <RiPuzzle2Line size={20} color="#7D52F4" />,
  },
  {
    name: "Help & Support",
    slug: "wcf-help-and-support",
    dIcon: <RiCustomerServiceLine size={20} color="#1FC16B" />,
  },
  {
    name: "Feedback",
    slug: "wcf-feedback",
    dIcon: <RiStarLine size={20} color="#FFA132" />,
  },
  {
    name: "Join Community",
    slug: "wcf-community",
    dIcon: <RiGroup3Line size={20} color="#7D52F4" />,
  },
  {
    name: "Latest Blogs & Articles",
    slug: "wcf-blog",
    dIcon: <RiNewsLine size={20} color="#47C2FF" />,
  },
];
