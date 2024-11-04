import MainHeader from "@/components/header/MainHeader";
import { useEffect, useState } from "react";

const MainLayout = ({ children }) => {
  const [open, setOpen] = useState(false);
  // const location = useLocation();

  // useEffect(() => {
  //   if (location.hash) {
  //     setTimeout(() => {
  //       const element = document.getElementById(location.hash.substring(1));
  //       if (element) {
  //         element.scrollIntoView({
  //           behavior: "smooth",
  //           block: "center",
  //         });

  //         const observer = new IntersectionObserver(
  //           (entries, observerInstance) => {
  //             entries.forEach((entry) => {
  //               if (entry.isIntersecting) {
  //                 setOpen(false);
  //                 observerInstance.unobserve(entry.target);
  //               }
  //             });
  //           },
  //           {
  //             root: null,
  //             threshold: 0.5,
  //           }
  //         );

  //         observer.observe(element);
  //       }
  //     }, 100);
  //   }
  // }, [location]);

  return (
    <div className="bg-background-secondary py-8">
      <div className="container overflow-x-hidden bg-background rounded-[10px]">
        <MainHeader open={open} setOpen={setOpen} />
        <div className="px-24 py-8">{children}</div>
      </div>
    </div>
  );
};

export default MainLayout;
