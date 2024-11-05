import {
  RiArrowRightUpLine,
  RiCustomerServiceLine,
  RiGroup3Line,
  RiStarLine,
} from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import { cn } from "@/lib/utils";

const ConnectWithUs = () => {
  // const location = useLocation();

  // const hashValue = location.hash.replace("#", "");
  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");
  console.log(hashValue);

  return (
    <div className="grid grid-cols-3 gap-6 h-full">
      <div
        className={cn(
          "border rounded-2xl p-5 shadow-common flex flex-col gap-[18px]",
          hashValue === "wcf-help-and-support"
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : ""
        )}
        id="wcf-help-and-support"
      >
        <div className="inline-flex items-center gap-2 bg-background-secondary self-start py-1 ps-1.5 pe-2 rounded">
          <RiCustomerServiceLine size={20} color="#1FC16B" />
          <p className="text-[13px]">Help & Support</p>
        </div>
        <div id="help">
          <h3 className="text-lg font-medium">Need Any Help?</h3>
          <p className="text-sm text-text-secondary mt-2">
            Feel like you want to consult with an expert? Take live chat support
            immediately from our{" "}
            {
              <a
                href="https://wealcoder.com/"
                className="text-[#2587EC] underline underline-offset-2"
              >
                Website
              </a>
            }
            .
          </p>
        </div>
        <div>
          <div
            href="#"
            className={cn(buttonVariants({ variant: "secondary" }), "w-full")}
          >
            Create a ticket{" "}
            <RiArrowRightUpLine
              size={20}
              className="ml-[6px]"
              color="#525866"
            />
          </div>
        </div>
      </div>
      <div
        className={cn(
          "border rounded-2xl p-5 shadow-common flex flex-col gap-[18px]",
          hashValue === "wcf-feedback"
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : ""
        )}
        id="wcf-feedback"
      >
        <div className="inline-flex items-center gap-2 bg-background-secondary self-start py-1 ps-1.5 pe-2 rounded">
          <RiStarLine size={20} color="#FFA132" />
          <p className="text-[13px]">Feedback</p>
        </div>
        <div>
          <h3 className="text-lg font-medium">Show Your Love</h3>
          <p className="text-sm text-text-secondary mt-2">
            If you are happy with our product and support, please support us by
            giving us <span className="text-[#FFA132]">★★★★★</span> 5 star
            rating.
          </p>
        </div>
        <div>
          <div
            href="#"
            className={cn(buttonVariants({ variant: "secondary" }), "w-full")}
          >
            Give your feedback{" "}
            <RiArrowRightUpLine
              size={20}
              className="ml-[6px]"
              color="#525866"
            />
          </div>
        </div>
      </div>
      <div
        className={cn(
          "border rounded-2xl p-5 shadow-common flex flex-col gap-[18px]",
          hashValue === "wcf-community"
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : ""
        )}
        id="wcf-community"
      >
        <div className="inline-flex items-center gap-2 bg-background-secondary self-start py-1 ps-1.5 pe-2 rounded">
          <RiGroup3Line size={20} color="#7D52F4" />
          <p className="text-[13px]">Join Community</p>
        </div>
        <div>
          <h3 className="text-lg font-medium">Contribute to Us</h3>
          <p className="text-sm text-text-secondary mt-2">
            Join our community of developers and designers and help us by
            recommending features.
          </p>
        </div>
        <div>
          <div
            href="#"
            className={cn(buttonVariants({ variant: "secondary" }), "w-full")}
          >
            Join Our Community{" "}
            <RiArrowRightUpLine
              size={20}
              className="ml-[6px]"
              color="#525866"
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default ConnectWithUs;
