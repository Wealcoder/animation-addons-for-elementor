import { LoadingSpinner } from "@/components/shared/LoadingSpinner";
import { Progress } from "@/components/ui/progress";
import DemoImportingBG from "../../public/images/demo-importing-bg.png";
import { useCallback, useEffect, useState } from "react";
import { debounceFn } from "@/lib/utils";

const DemoImporting = () => {
 const url = new URL(window.location.href);
 const template = url.searchParams.get("template");
 const templateid = url.searchParams.get("templateid");
 const [currenTemplate, setCurrenTemplate] = useState(false);
 const [msg, setMsg] = useState("");
 const [tempstate, setTempState] = useState(null);

 const [step, setStep] = useState("Varifying");
  useEffect(() => { 
    if(!currenTemplate){
      getTemplate(templateid);
    }else{      
      runImport(currenTemplate);      
    }     
  
    const interval = setInterval(fetchHeartbeatData, 5000);
    return () => clearInterval(interval);
  }, [currenTemplate]);

    const getTemplate = useCallback(
      debounceFn(async (id) => {
        try {        
          const url = new URL(
            `${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/wp/v2/starter-templates`
          );
  
          if (id) {
            url.searchParams.append("tplid", id);
          }        
  
          await fetch(url.toString())
            .then((response) => response.json())
            .then((data) => { 
                
              if(data?.templates){
                const result = Object.entries(data.templates).find(
                  ([key, value]) => value.id == id
                )?.[1];    
                                      
                setCurrenTemplate(result);    
                         
              }            
              
            });
        } catch (error) {
          console.log(error);
        }
      }),
      []
    );

    const fetchHeartbeatData = async () => {
      try {
        const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl,{
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'aaeaddon_heartbeat_data', nonce: WCF_ADDONS_ADMIN.nonce }),
        }); 
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        const jsonData = await response.json();  
        if(jsonData.msg){          
          setMsg(jsonData.msg);
        }else{

        }      
       
      } catch (err) {}      
    };

    const runImport = useCallback(
      debounceFn(async (tpldata) => {         
        try {      
          delete tpldata.author;
          delete tpldata.categories;
          delete tpldata.date;
          delete tpldata.demo_link;
          delete tpldata.user_favourite;
          delete tpldata.template_preview;
          delete tpldata.downloads;
          delete tpldata.is_pro;
          delete tpldata.excerpt;
          setTempState(tpldata);
          const formData = new URLSearchParams();
          formData.append("action", "aaeaddon_template_installer"); // Custom action name
          formData.append("template_data", JSON.stringify(tpldata)); // Optional custom data
          formData.append("nonce", WCF_ADDONS_ADMIN.nonce); // Assuming nonce is available in the object
    
          const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: formData.toString(),
          });
      
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }  
          const contentType = response.headers.get("content-type");  
          
          if (contentType && contentType.includes("application/json")) {
            const data = await response.json();
           
            if (data?.template) {          
              if(data.template.next_step != 'done'){
                runImport(data.template);             
                setStep(data.template.next_step);
                setMsg(data.msg);
              }else if(data.template.next_step == 'fail'){
                setMsg(data.msg);
              }
            }
          } else {           
            runImport(tpldata);            
           }
         
        } catch (error) {          
          if(tempstate){
            runImport(tempstate);
          }          
        }
      }, 300),
      []
    );
    
  return (
    <div className="bg-background w-[680px] rounded-2xl p-1.5 shadow-auth-card">
      <div className="border border-border-secondary rounded-xl p-8">
        <div className="mb-6">
          <h3 className="text-2xl font-medium">{!currenTemplate ? 'Template finding.....' : 'Creating your website...'} </h3>
          <p className="mt-1.5 text-text-secondary">
            Please wait, your website is being created.
          </p>
        </div>
        <div className="mb-8">
          <img
            src={DemoImportingBG}
            className="w-[616px] h-[258px]"
            alt="demo importing"
          />
        </div>
        <div>
          <p className="text-text-secondary">
            <span className="text-text">{step} :</span> {msg}
          </p>
          <div className="grid grid-cols-4 items-center gap-1 mt-4">
            <span>
              <Progress value={100} />
            </span>
            <span>
              <Progress value={0} />
            </span>
            <span>
              <Progress value={0} />
            </span>
            <span>
              <Progress value={0} />
            </span>
          </div>
          <div className="flex items-center gap-1.5 mt-3">
            <LoadingSpinner className={"text-[#07B22B]"} />{" "}
            <p className="text-sm">
              <span>25%</span> Completed
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DemoImporting;
