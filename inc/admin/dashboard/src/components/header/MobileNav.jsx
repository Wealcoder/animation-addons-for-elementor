import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "../ui/button";
import { RiMenuLine } from "react-icons/ri";
import { useEffect, useState } from "react";
import { cn } from "@/lib/utils";
import { MainNavData } from "@/config/nav/main-nav";

const MobileNav = ({ NavigateComponent }) => {
  const [currentPath, setCurrentPath] = useState("");
  const navItems = MainNavData;
  const role = WCF_ADDONS_ADMIN.user_role;

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setCurrentPath(tabValue);
    } else {
      setCurrentPath("dashboard");
    }
  }, [urlParams]);

  if (!(navItems && navItems.length)) return;

  const changeRoute = (value) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    NavigateComponent(value);
    setCurrentPath(value);
  };

  function hasCommonElement(array1, array2) {
    return array1?.some((item) => array2.includes(item));
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="secondary" size="icon">
          <RiMenuLine size={20} />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent className="w-48" align={"end"}>
        {navItems.map((item) => (
          <DropdownMenuItem
            key={item.path}
            className={cn(hasCommonElement(item?.role, role) ? "" : "hidden")}
          >
            {item.targetBlank ? (
              <a
                href={item.path}
                target="_blank"
                className={cn(
                  currentPath === item.path.split("/").pop()
                    ? "bg-background-secondary text-text-secondary-hover"
                    : "",
                  "w-max items-center justify-center bg-background ps-2.5 pe-3 py-2 font-medium transition-colors hover:bg-background-secondary hover:text-text-secondary-hover flex rounded-lg gap-2 text-base text-text-secondary"
                )}
              >
                {item.name}
                <span
                  className={cn(
                    "flex",
                    currentPath === item.path.split("/").pop()
                      ? "text-text-hover"
                      : ""
                  )}
                >
                  {item.icon}
                </span>
              </a>
            ) : (
              <div
                className={cn(
                  currentPath === item.path.split("/").pop()
                    ? "bg-background-secondary text-text-secondary-hover"
                    : "",
                  "w-max items-center justify-center bg-background ps-2.5 pe-3 py-2 font-medium transition-colors hover:bg-background-secondary hover:text-text-secondary-hover flex rounded-lg gap-2 text-base text-text-secondary"
                )}
                onClick={() => changeRoute(item.path)}
              >
                <span
                  className={cn(
                    "flex",
                    currentPath === item.path.split("/").pop()
                      ? "text-text-hover"
                      : ""
                  )}
                >
                  {item.icon}
                </span>
                {item.name}
              </div>
            )}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
};

export default MobileNav;
