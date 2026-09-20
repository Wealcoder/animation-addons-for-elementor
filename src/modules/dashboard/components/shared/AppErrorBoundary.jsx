import { Component } from "react";
import { __ } from "@wordpress/i18n";

/**
 * The last line between a render error and a BLANK dashboard.
 *
 * React 18 unmounts the whole root when a commit throws and nothing above it
 * catches — the screen goes white with nothing on it but a console line most
 * people never open. The first real case was a WPML site: Chrome's page
 * translation had rewritten the text nodes, React's next commit hit
 * "Failed to execute 'removeChild' on 'Node'", and the Legacy (V3) click
 * emptied the page. The mount node now opts out of translation (see
 * dashboard.php), and this boundary is for whatever the NEXT cause is: the
 * page keeps its chrome, says what happened, and offers a reload.
 *
 * Nothing is retried in place — a tree that threw mid-commit is not one to
 * keep rendering into. Reload is the honest recovery.
 */
class AppErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { error: null };
  }

  static getDerivedStateFromError(error) {
    return { error };
  }

  componentDidCatch(error, info) {
    // Keep the console line: it is the only trace a support ticket can carry.
    // eslint-disable-next-line no-console
    console.error("Animation Addons dashboard:", error, info?.componentStack);
  }

  render() {
    const { error } = this.state;

    if (!error) {
      return this.props.children;
    }

    const translated =
      typeof document !== "undefined" &&
      !!document.querySelector("html.translated-ltr, html.translated-rtl, font[style]");

    return (
      <div
        className="mx-auto my-16 max-w-xl rounded-[10px] border bg-background-secondary p-8 text-center"
        data-aae-app-error
        translate="no"
      >
        <h2 className="mb-2 text-lg font-semibold text-text">
          {__("The dashboard stopped responding", "animation-addons-for-elementor")}
        </h2>
        <p className="mb-4 text-sm text-text-secondary">
          {translated
            ? __(
                "Your browser translated this page, which breaks the dashboard's live updates. Turn off page translation for this site and reload.",
                "animation-addons-for-elementor"
              )
            : __(
                "Something on this page threw an error while it was updating. Reloading brings it back; if it happens again, the browser console has the details.",
                "animation-addons-for-elementor"
              )}
        </p>
        <button
          type="button"
          className="rounded-md bg-button px-4 py-2 text-sm font-medium text-button-text"
          onClick={() => window.location.reload()}
        >
          {__("Reload", "animation-addons-for-elementor")}
        </button>
        <pre className="mt-6 max-h-40 overflow-auto rounded-md border p-3 text-left text-xs text-text-secondary">
          {String(error?.message || error)}
        </pre>
      </div>
    );
  }
}

export default AppErrorBoundary;
