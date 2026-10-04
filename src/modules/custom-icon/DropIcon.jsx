import { UploadCloud, X } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { useDropzone } from "react-dropzone";

export default function DropIcon() {
  const [uploadedFiles, setUploadedFiles] = useState([]);
  const [filesToUpload, setFilesToUpload] = useState([]);
  const [titleText, setTitleText] = useState("");
  const [error, setError] = useState(null);
  const [message, setMessage] = useState("");

  useEffect(() => {
    document.addEventListener("DOMContentLoaded", trackInputValue);
  }, []);

  function trackInputValue() {
    // Get the input field inside the div with id 'titlewrap'
    const titleWrap = document.getElementById("titlewrap");
    if (!titleWrap) {
      return;
    }

    const inputField = titleWrap.querySelector("input");
    if (!inputField) {
      return;
    }

    // Add event listener to capture input changes
    inputField.addEventListener("input", (event) => {
      setTitleText(event.target.value);
    });

    setTitleText(inputField.value);
  }

  const uploadFile = (file) => {
    setUploadedFiles([]);
    return new Promise((resolve, reject) => {
      const formData = new FormData();
      formData.append("custom_icon", file);
      formData.append("id", WCF_ADDONS_ADMIN.id);
      formData.append("action", "aaeaddon_upload_custom_icon_zip");
      formData.append("nonce", WCF_ADDONS_ADMIN.nonce);

      // Plain XMLHttpRequest: it is the one browser API with upload
      // progress and abort, which is all this needs. axios used to do this
      // and was never a declared dependency -- it resolved by accident
      // through the build tooling's own lockfile.
      const xhr = new XMLHttpRequest();
      let cancelled = false;

      setFilesToUpload((prevUploadProgress) =>
        prevUploadProgress.map((item) =>
          item.File.name === file.name
            ? {
                ...item,
                source: {
                  cancel: () => {
                    cancelled = true;
                    xhr.abort();
                  },
                },
              }
            : item
        )
      );

      xhr.upload.addEventListener("progress", (event) => {
        const progress = event.lengthComputable
          ? Math.round((event.loaded / event.total) * 100)
          : 0;

        setFilesToUpload((prevUploadProgress) =>
          prevUploadProgress.map((item) =>
            item.File.name === file.name ? { ...item, progress } : item
          )
        );
      });

      xhr.addEventListener("load", () => {
        let body = null;
        try {
          body = JSON.parse(xhr.responseText);
        } catch (e) {
          body = null;
        }

        if (xhr.status < 200 || xhr.status >= 300 || !body || body.success === false) {
          const reason =
            body?.data?.message || `Upload failed (HTTP ${xhr.status})`;
          console.error(reason);
          reject(new Error(reason));
          return;
        }

        setUploadedFiles([file]);
        setMessage(body?.data?.message);
        setFilesToUpload((prevUploadProgress) =>
          prevUploadProgress.filter((item) => item.File !== file)
        );

        resolve();
      });

      xhr.addEventListener("error", () => {
        console.error("Network error");
        reject(new Error("Network error"));
      });

      xhr.addEventListener("abort", () => {
        if (cancelled) {
          console.log("Upload canceled");
        }
        reject(new Error("Upload canceled"));
      });

      // No Content-Type header: the browser writes the multipart boundary.
      xhr.open("POST", WCF_ADDONS_ADMIN.ajaxurl);
      xhr.send(formData);
    });
  };

  const onDrop = useCallback(
    async (acceptedFiles, rejectedFiles) => {
      setError(null); // Reset the error message

      if (rejectedFiles.length > 0) {
        setError("Only ZIP files are allowed.");
        return;
      }

      const newFiles = acceptedFiles.map((file) => ({
        progress: 0,
        File: file,
        source: null,
      }));

      setFilesToUpload((prevUploadProgress) => [
        ...prevUploadProgress,
        ...newFiles,
      ]);

      for (const file of acceptedFiles) {
        try {
          await uploadFile(file);
        } catch (err) {
          console.error(err.message);
        }
      }
    },
    [uploadFile]
  );

  const { getRootProps, getInputProps } = useDropzone({
    onDrop,
    accept: {
      "application/zip": [".zip"], // Restrict to ZIP files
    },
  });

  if (!titleText) return;

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: "25px" }}>
      <div>
        <label {...getRootProps()} className="dropzone">
          <div className="dropzone-content">
            <div className="upload-icon">
              <UploadCloud size={20} />
            </div>
            <p className="drag-text">
              <span className="font-semibold">Drag files</span>
            </p>
            <p className="file-info">
              Click to upload files &#40;only ZIP files under 10 MB&#41;
            </p>
            <p style={{ marginTop: "10px" }}>
              Only{" "}
              <span>
                <a
                  href="https://icomoon.io"
                  style={{ color: "#F58E2F", textDecoration: "none" }}
                  target="_blank"
                >
                  icomoon
                </a>
              </span>{" "}
              supported
            </p>
          </div>
        </label>

        <input
          {...getInputProps()}
          id="dropzone-file"
          type="file"
          className="hidden"
        />
      </div>

      {error && (
        <div className="error-message">
          <p>{error}</p>
        </div>
      )}

      {filesToUpload.length > 0 && (
        <div>
          <p className="section-title">Files to upload</p>
          <div className="file-list">
            {filesToUpload.map((fileUploadProgress) => (
              <div
                key={fileUploadProgress.File.lastModified}
                className="file-item"
              >
                <div className="file-details">
                  <div className="file-icon">
                    {fileUploadProgress.File.name.endsWith(".zip") && (
                      <UploadCloud size={20} />
                    )}
                  </div>
                  <div className="file-info">
                    <div className="file-header">
                      <p>{fileUploadProgress.File.name.slice(0, 25)}</p>
                      <span>{fileUploadProgress.progress}%</span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {uploadedFiles.length > 0 && (
        <>
          <div>
            <p className="section-title">Uploaded Files</p>
            <div className="file-list">
              {uploadedFiles.map((file) => (
                <div key={file.lastModified} className="file-item uploaded">
                  <div className="file-details">
                    <div className="file-icon">
                      {file.name.endsWith(".zip") && <UploadCloud size={20} />}
                    </div>
                    <div className="file-info">
                      <div className="file-header">
                        <p>{file.name.slice(0, 25)}</p>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
          <div>
            <p
              className="section-title"
              style={{
                color: "#F58E2F",
                fontSize: "24px",
                textAlign: "center",
              }}
            >
              {message}
            </p>
          </div>
        </>
      )}
    </div>
  );
}
