import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from "@/components/ui/navigation-menu";
import { MainNavData } from "@/config/nav/main-nav";
import { cn } from "@/lib/utils";

const MainNavOld = () => {
  // const location = useLocation();
  const navItems = MainNavData;

  // const currentPath = location.pathname.split("/").pop();
  const currentPath = "";

  if (!(navItems && navItems.length)) return;

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
                  className={cn(
                    navigationMenuTriggerStyle(),
                    "rounded-lg gap-2 text-base text-text-secondary"
                  )}
                  target="_blank"
                >
                  {item.name}
                  <span className="group-hover/item:text-text-hover group-data-[active]/item:text-text-hover">
                    {item.icon}
                  </span>
                </a>
              </NavigationMenuLink>
            ) : (
              <NavigationMenuLink
                asChild
                active={currentPath === item.path.split("/").pop()}
              >
                <a
                  href={item.path}
                  className={cn(
                    navigationMenuTriggerStyle(),
                    "rounded-lg gap-2 text-base text-text-secondary"
                  )}
                >
                  <span className="group-hover/item:text-text-hover group-data-[active]/item:text-text-hover">
                    {item.icon}
                  </span>
                  {item.name}
                </a>
              </NavigationMenuLink>
            )}
          </NavigationMenuItem>
        ))}
      </NavigationMenuList>
    </NavigationMenu>
  );
};

export default MainNavOld;
