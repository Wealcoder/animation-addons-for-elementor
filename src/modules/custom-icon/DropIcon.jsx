import { UploadCloud, X } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { useDropzone } from "react-dropzone";
import axios from "axios";

export default function DropIcon() {
  const [uploadedFiles, setUploadedFiles] = useState([]);
  const [filesToUpload, setFilesToUpload] = useState([]);
  const [titleText, setTitleText] = useState("");
  const [error, setError] = useState(null);

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

      axios
        .post(WCF_ADDONS_ADMIN.ajaxurl, formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
          onUploadProgress: (progressEvent) => {
            const progress = Math.round(
              (progressEvent.loaded / (progressEvent.total ?? 0)) * 100
            );

            setFilesToUpload((prevUploadProgress) =>
              prevUploadProgress.map((item) =>
                item.File.name === file.name ? { ...item, progress } : item
              )
            );
          },
          cancelToken: new axios.CancelToken((cancel) => {
            setFilesToUpload((prevUploadProgress) =>
              prevUploadProgress.map((item) =>
                item.File.name === file.name
                  ? { ...item, source: { cancel } }
                  : item
              )
            );
          }),
        })
        .then(() => {
          setUploadedFiles([file]);

          setFilesToUpload((prevUploadProgress) =>
            prevUploadProgress.filter((item) => item.File !== file)
          );

          resolve();
        })
        .catch((err) => {
          if (axios.isCancel(err)) {
            console.log("Upload canceled", err.message);
          } else {
            console.error(err.message);
          }
          reject(err);
        });
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
              style={{ color: "#F58E2F", fontSize: "24px", textAlign: "center" }}
            >
              Icon Generated
            </p>
          </div>
        </>
      )}
    </div>
  );
}
