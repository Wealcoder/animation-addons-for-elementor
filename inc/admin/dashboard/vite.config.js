/* eslint-disable no-undef */
import path from "path"
import react from "@vitejs/plugin-react"
import { defineConfig } from "vite"

export default defineConfig({
  build: {
    rollupOptions: {      
      output: {
        entryFileNames: `assets/[name].js`, // or replace [name] with a specific name
        chunkFileNames: `assets/[name].js`,
        assetFileNames: `assets/[name].[ext]`,       
      },
    },
  },
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
})



