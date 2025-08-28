import { useEffect, useState } from "react";
import { generateUniqueId } from "../../utils/generateUniqueId";

const SampleData = {
  id: generateUniqueId(),
  fontWeight: {
    label: "Font Weight",
    value: "",
  },
  style: {
    label: "Style",
    value: "",
  },
  woff: {
    label: "WOFF FILE",
    file: {
      id: "",
      url: "",
      message: "",
    },
  },
  woff2: {
    label: "WOFF2 FILE",
    file: {
      id: "",
      url: "",
      message: "",
    },
  },
  ttf: {
    label: "TTF FILE",
    file: {
      id: "",
      url: "",
      message: "",
    },
  },
  otf: {
    label: "OTF FILE",
    file: {
      id: "",
      url: "",
      message: "",
    },
  },
  eot: {
    label: "EOT FILE",
    file: {
      id: "",
      url: "",
      message: "",
    },
  },
};

const CustomFont = () => {
  const [allFont, setAllFont] = useState(
    JSON.parse(WCF_ADDONS_ADMIN.data) ?? [SampleData]
  );

  const fontFamilyName = document.getElementById("title").value;

  useEffect(() => {
    document.getElementById("publish").classList.add("published_js");
    document.getElementById("publish").addEventListener("click", handleClick);

    return () => {
      document
        .getElementById("publish")
        .removeEventListener("click", handleClick);
    };
  }, [allFont]);

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
        action: "wcf_save_custom_fonts",
        fields: JSON.stringify(allFont),
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

  const uploadFont = (e, id, key) => {
    e.preventDefault();
    let custom_uploader;

    if (undefined !== custom_uploader) {
      custom_uploader.open();
      return;
    }
    custom_uploader = wp.media.frames.file_frame = wp.media({
      frame: "post",
      state: "insert",
      multiple: false,
      button: {
        text: "Use this font",
      },
      library: {
       // type: [key], // Restrict to allowed font types
      },
    });

    custom_uploader.on("insert", function () {
      var selection = custom_uploader.state().get("selection");

      selection.each(function (attachment, index) {
        attachment = attachment.toJSON();
        if (0 === index) {
          if (attachment.filename.split(".")[1] === key) {
            setAllFont(
              allFont.map((task) =>
                task.id === id
                  ? {
                      ...task,
                      [key]: {
                        ...task[key],
                        file: {
                          id: attachment.id,
                          url: attachment.url,
                          message: "",
                        },
                      },
                    }
                  : task
              )
            );
          } else {
            setAllFont(
              allFont.map((task) =>
                task.id === id
                  ? {
                      ...task,
                      [key]: {
                        ...task[key],
                        file: {
                          id: "",
                          url: "",
                          message: "File formate not support",
                        },
                      },
                    }
                  : task
              )
            );
            setTimeout(() => {
              setAllFont(
                allFont.map((task) =>
                  task.id === id
                    ? {
                        ...task,
                        [key]: {
                          ...task[key],
                          file: {
                            id: "",
                            url: "",
                            message: "",
                          },
                        },
                      }
                    : task
                )
              );
            }, [1000]);
          }
        }
      });
    });

    custom_uploader.open();
  };

  const addNewFont = (e, SampleData) => {
    e.preventDefault();
    const newData = { ...SampleData, id: generateUniqueId() };
    setAllFont((prev) => [...prev, newData]);
  };

  const handleSelectChange = (event, id, key) => {
    event.preventDefault();
    setAllFont(
      allFont.map((task) =>
        task.id === id
          ? { ...task, [key]: { ...task[key], value: event.target.value } }
          : task
      )
    );
  };

  const removeFont = (e, id) => {
    e.preventDefault();
    setAllFont(allFont.filter((task) => task.id !== id));
  };

  const removeUpload = (e, id, key) => {
    e.preventDefault();
    setAllFont(
      allFont.map((task) =>
        task.id === id
          ? {
              ...task,
              [key]: {
                ...task[key],
                file: { id: "", url: "", message: "" },
              },
            }
          : task
      )
    );
  };

  const styleUrl = [];

  allFont.forEach((font) => {
    if (font.eot.file.url) {
      styleUrl.push(`url('${font.eot.file.url}') format('embedded-opentype')`);
    }
    if (font.woff2.file.url) {
      styleUrl.push(`url('${font.woff2.file.url}') format('woff2')`);
    }
    if (font.woff.file.url) {
      styleUrl.push(`url('${font.woff.file.url}') format('woff')`);
    }
    if (font.ttf.file.url) {
      styleUrl.push(`url('${font.ttf.file.url}') format('truetype')`);
    }
    if (font.otf.file.url) {
      styleUrl.push(`url('${font.otf.file.url}') format('truetype')`);
    }
  });

  const dynamicStyle = `@font-face {
      font-family: ${fontFamilyName};
      src: ${styleUrl.join()};
    }`;

  return (
    <>
      <style>{dynamicStyle.split(",").join(";")}</style>
      <div style={{ padding: "30px" }}>
        <div
          style={{
            border: "1px solid #E1E4EA",
            borderRadius: "6px",
          }}
        >
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "1fr 1fr 4fr",
              alignItems: "center",
              padding: "12px 30px",
              marginBottom: "20px",
              borderBottom: "1px solid #E1E4EA",
            }}
          >
            <p style={{ fontSize: "16px", margin: "0" }}>Font Family</p>
            <p style={{ fontSize: "16px", margin: "0" }}>Font Weight</p>
            <p
              style={{
                fontSize: "16px",
                margin: "0",
              }}
            >
              Text
            </p>
          </div>
          {allFont.map((item) => (
            <div
              style={{
                display: "grid",
                gridTemplateColumns: "1fr 1fr 4fr",
                alignItems: "center",
                padding: "12px 30px",
              }}
              key={item.id}
            >
              <p style={{ fontSize: "16px", margin: "0" }}>{fontFamilyName}</p>
              <p style={{ fontSize: "16px", margin: "0" }}>
                {item.fontWeight.value}
              </p>
              <p
                style={{
                  fontSize: "26px",
                  margin: "0",
                  fontWeight: item.fontWeight.value,
                  fontStyle: item.style.value,
                  fontFamily: fontFamilyName,
                }}
              >
                The quick brown fox jumps over the lazy dog
              </p>
            </div>
          ))}
        </div>
      </div>
      <div className="wcf-custom-font">
        {allFont?.map((item, i) => (
          <div className="section-group" key={`custom_font-${i}`}>
            <div
              style={{
                display: "flex",
                justifyContent: "space-between",
                borderBottom: "1px solid #E1E4EA",
                paddingBottom: "10px",
              }}
            >
              <div>
                <h3 style={{ margin: "0" }}>Font Variant {i + 1}</h3>
              </div>
              <div
                style={{ cursor: "pointer" }}
                onClick={(e) => removeFont(e, item.id)}
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  class="lucide lucide-trash-2"
                >
                  <path d="M3 6h18" />
                  <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                  <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                  <line x1="10" x2="10" y1="11" y2="17" />
                  <line x1="14" x2="14" y1="11" y2="17" />
                </svg>
              </div>
            </div>
            <div className="font-group" style={{ paddingTop: "10px" }}>
              <h3>{item.fontWeight.label}</h3>
              <select
                value={item.fontWeight.value}
                onChange={(e) => handleSelectChange(e, item.id, "fontWeight")}
              >
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="300">300</option>
                <option value="400">400 Regular</option>
                <option value="500">500</option>
                <option value="600">600</option>
                <option value="700">700</option>
                <option value="800">800</option>
                <option value="900">900</option>
              </select>
            </div>
            <div className="font-group">
              <h3>{item.style.label}</h3>
              <select
                value={item.style.value}
                onChange={(e) => handleSelectChange(e, item.id, "style")}
              >
                <option value="normal">Normal</option>
                <option value="italic">Italic</option>
                <option value="oblique">Oblique</option>
              </select>
            </div>
            {item.woff && (
              <div className="font-group">
                <h3>{item.woff.label}</h3>
                <div className="input-upload">
                  <div style={{ width: "100%" }}>
                    <input
                      placeholder="The Web Open Font Formate"
                      value={item.woff.file.url}
                    />
                    {item.woff.file.message ? (
                      <small style={{ margin: "0", color: "#fc6b4a" }}>
                        {item.woff.file.message}
                      </small>
                    ) : (
                      ""
                    )}
                  </div>
                  <div className="button-upload-remove">
                    <button
                      type="button"
                      className="upload"
                      onClick={(e) => uploadFont(e, item.id, "woff")}
                    >
                      Upload
                    </button>
                    <button
                      type="button"
                      className="remove"
                      onClick={(e) => removeUpload(e, item.id, "woff")}
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            )}

            {item.woff2 && (
              <div className="font-group">
                <h3>{item.woff2.label}</h3>
                <div className="input-upload">
                  <div style={{ width: "100%" }}>
                    <input
                      placeholder="The Web Open Font Formate 2. Used by modern browser"
                      value={item.woff2.file.url}
                    />
                    {item.woff2.file.message ? (
                      <small style={{ margin: "0", color: "#fc6b4a" }}>
                        {item.woff2.file.message}
                      </small>
                    ) : (
                      ""
                    )}
                  </div>
                  <div className="button-upload-remove">
                    <button
                      type="button"
                      className="upload"
                      onClick={(e) => uploadFont(e, item.id, "woff2")}
                    >
                      Upload
                    </button>
                    <button
                      type="button"
                      className="remove"
                      onClick={(e) => removeUpload(e, item.id, "woff2")}
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            )}

            {item.ttf && (
              <div className="font-group">
                <h3>{item.ttf.label}</h3>
                <div className="input-upload">
                  <div style={{ width: "100%" }}>
                    <input
                      placeholder="The True Type Font Formate. Best used for safari, android, ios"
                      value={item.ttf.file.url}
                    />
                    {item.ttf.file.message ? (
                      <small style={{ margin: "0", color: "#fc6b4a" }}>
                        {item.ttf.file.message}
                      </small>
                    ) : (
                      ""
                    )}
                  </div>
                  <div className="button-upload-remove">
                    <button
                      type="button"
                      className="upload"
                      onClick={(e) => uploadFont(e, item.id, "ttf")}
                    >
                      Upload
                    </button>
                    <button
                      type="button"
                      className="remove"
                      onClick={(e) => removeUpload(e, item.id, "ttf")}
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            )}

            {item.otf && (
              <div className="font-group">
                <h3>{item.otf.label}</h3>
                <div className="input-upload">
                  <div style={{ width: "100%" }}>
                    <input
                      placeholder="Open Type. Best use for web"
                      value={item.otf.file.url}
                    />
                    {item.otf.file.message ? (
                      <small style={{ margin: "0", color: "#fc6b4a" }}>
                        {item.otf.file.message}
                      </small>
                    ) : (
                      ""
                    )}
                  </div>
                  <div className="button-upload-remove">
                    <button
                      type="button"
                      className="upload"
                      onClick={(e) => uploadFont(e, item.id, "otf")}
                    >
                      Upload
                    </button>
                    <button
                      type="button"
                      className="remove"
                      onClick={(e) => removeUpload(e, item.id, "otf")}
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            )}

            {item.eot && (
              <div className="font-group">
                <h3>{item.eot.label}</h3>
                <div className="input-upload">
                  <div style={{ width: "100%" }}>
                    <input
                      placeholder="Embedded Open Type. Best used for IE6-9"
                      value={item.eot.file.url}
                    />
                    {item.eot.file.message ? (
                      <small style={{ margin: "0", color: "#fc6b4a" }}>
                        {item.eot.file.message}
                      </small>
                    ) : (
                      ""
                    )}
                  </div>
                  <div className="button-upload-remove">
                    <button
                      type="button"
                      className="upload"
                      onClick={(e) => uploadFont(e, item.id, "eot")}
                    >
                      Upload
                    </button>
                    <button
                      type="button"
                      className="remove"
                      onClick={(e) => removeUpload(e, item.id, "eot")}
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        ))}
        <div className="create-new-font">
          <button
            onClick={(e) => addNewFont(e, SampleData)}
            style={{
              height: "100%",
              backgroundColor: "#fc6b4a",
              border: "none",
              color: "white",
              padding: "6px 12px",
              borderRadius: "8px",
            }}
          >
            Add New
          </button>
        </div>
      </div>
    </>
  );
};

export default CustomFont;
