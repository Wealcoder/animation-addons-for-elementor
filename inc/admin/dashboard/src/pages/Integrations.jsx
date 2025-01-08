import IntegrationTopBar from "@/components/integrations/IntegrationTopBar";
import ShowIntegrations from "@/components/integrations/ShowIntegrations";
import { buttonVariants } from "@/components/ui/button";
import { PromotionList } from "@/config/data/promotionList";
import { cn } from "@/lib/utils";

const Integrations = () => {
  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <IntegrationTopBar />
      </div>
      <div className="mt-4">
        <ShowIntegrations />

        {/* promotion  */}
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mt-11">
          {PromotionList?.map((item, i) => (
            <div
              key={`promotion-${i}`}
              className="border rounded-2xl p-5 ps-6 flex flex-col gap-6 shadow-common"
            >
              <div>
                <div>
                  <img
                    className="w-full h-full object-cover"
                    src={item.image}
                    alt="thumbnail"
                  />
                </div>
              </div>
              <div>
                <h2 className="text-xl font-medium ">{item.title}</h2>
                <p className="text-sm mt-[10px] text-text-secondary line-clamp-3">
                  {item.description}
                </p>
                <a
                  href={item.url}
                  className={cn(buttonVariants(), "mt-7 px-11")}
                  target="_blank"
                >
                  Buy Now
                </a>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Integrations;
