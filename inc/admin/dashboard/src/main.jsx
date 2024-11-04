import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { RouterProvider } from "react-router-dom";
import routes from "./routes/index.jsx";

document.addEventListener('DOMContentLoaded', function(){
  console.log(document.getElementById("wcfadmindscrjs"));
  createRoot(document.getElementById("wcfadmindscrjs")).render(
    <StrictMode>
      <RouterProvider router={routes} />
    </StrictMode>
  );
}, false);



