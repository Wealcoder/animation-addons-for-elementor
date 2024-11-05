import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from "@/components/ui/navigation-menu";
import { MainNavData } from "@/config/nav/main-nav";
import { cn } from "@/lib/utils";
import { useEffect, useState } from "react";
// import queryString from "query-string";

const MainNav = ({ NavigateComponent }) => {
  const [currentPath, setCurrentPath] = useState("");
  const navItems = MainNavData;

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setCurrentPath(tabValue);
    }
  }, [urlParams]);

  if (!(navItems && navItems.length)) return;

  const changeRoute = (value) => {
    const url = new URL(window.location.href);
    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    NavigateComponent(value);
    setCurrentPath(value);
    // window.location.href = url;
  };
  // const changeRoute = (value) => {
  //   const nav = queryString.parse(location.search);
  //   nav.tab = value;
  //   location.search = queryString.stringify(nav);
  // };

  return (
    <NavigationMenu>
      <NavigationMenuList>
        {navItems.map((item) => (
          <NavigationMenuItem key={item.path}>
            {item.targetBlank ? (
              <NavigationMenuLink
                asChild
                active={currentPath === item.path.split("/").pop()}
              >
                <a
                  href={item.path}
                  target="_blank"
                  className={cn(
                    navigationMenuTriggerStyle(),
                    "rounded-lg gap-2 text-base text-text-secondary"
                  )}
                >
                  {item.name}
                  <span className="group-data-[active]/item:text-text-hover">
                    {item.icon}
                  </span>
                </a>
              </NavigationMenuLink>
            ) : (
              <NavigationMenuLink
                asChild
                active={currentPath === item.path.split("/").pop()}
              >
                <div
                  className={cn(
                    navigationMenuTriggerStyle(),
                    "rounded-lg gap-2 text-base text-text-secondary"
                  )}
                  onClick={() => changeRoute(item.path)}
                >
                  <span className="group-data-[active]/item:text-text-hover">
                    {item.icon}
                  </span>
                  {item.name}
                </div>
              </NavigationMenuLink>
            )}
          </NavigationMenuItem>
        ))}
      </NavigationMenuList>
    </NavigationMenu>
  );
};

export default MainNav;
