import { useEffect, useState } from "react";

const GlobalSettings = () => {
  const [isChecked, setIsChecked] = useState(
    WCF_ADDONS_ADMIN.custom_font_global == "true" ? true : false || false
  );

  useEffect(() => {
    document.getElementById("publish").classList.add("published_js");
    document.getElementById("publish").addEventListener("click", handleClick);

    return () => {
      document
        .getElementById("publish")
        .removeEventListener("click", handleClick);
    };
  }, [isChecked]);

  const handleClick = async (e) => {
    const publishButton = document.getElementById("publish");
    if (publishButton.classList.contains("published_js")) {
      e.preventDefault();
    }
    const form = publishButton.closest("form");

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_save_custom_fonts_settings",
        custom_font_global: JSON.stringify(isChecked),
        nonce: WCF_ADDONS_ADMIN.nonce,
        id: WCF_ADDONS_ADMIN.id,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        publishButton.classList.remove("published_js");
        form.submit();
      });
  };

  return (
    <div className="wcf_switch-toggle">
      <p>Enable For Global</p>
      <label className="switch">
        <input
          type="checkbox"
          checked={isChecked}
          onChange={(e) => setIsChecked(e.target.checked)}
        />
        <span className="slider round"></span>
      </label>
    </div>
  );
};

export default GlobalSettings;
