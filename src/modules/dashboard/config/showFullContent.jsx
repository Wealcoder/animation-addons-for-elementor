import MainLayout from "@/layouts/MainLayout";
import AnimationSettings from "@/pages/AnimationSettings";
import CompleteImport from "@/pages/CompleteImport";
import Dashboard from "@/pages/Dashboard";
import DemoImporting from "@/pages/DemoImporting";
import Extensions from "@/pages/Extensions";
import FailImport from "@/pages/FailImport";
import Integrations from "@/pages/Integrations";
import Performance from "@/pages/Performance";
import RequiredFeatures from "@/pages/RequiredFeatures";
import StaterTemplate from "@/pages/StaterTemplate";
import Submissions from "@/pages/Submissions";
import Widgets from "@/pages/Widgets";

/**
 * Tabs that no longer have a screen and are answered by another one.
 *
 * Distinct from `performance` and `integrations` below: those screens MOVED
 * into Animation Settings as tabs and kept their own `case` here, so the old
 * URL still names something real. A retired tab has no screen left at all, so
 * pointing its URL at a replacement would leave the address bar describing a
 * page nobody can reach. It resolves and then rewrites itself instead —
 * MainLayout does the rewrite, this map is the single place that decides it.
 *
 * `free-pro` — the Free vs Pro comparison left the dashboard 2026-09-06 and
 * Settings took its sidebar slot. pages/FreePro.jsx and its ComparisonTable
 * are still on disk, just unrouted.
 */
export const LEGACY_TAB_ALIASES = {
  "free-pro": "animation-settings",
};

export const resolveTabKey = (tabKey) => LEGACY_TAB_ALIASES[tabKey] || tabKey;

export const ShowContent = (item) => {
  switch (resolveTabKey(item.tabKey)) {
    case "dashboard":
      return (
        <MainLayout.FirstLayout>
          <Dashboard />
        </MainLayout.FirstLayout>
      );
    case "widgets":
      return (
        <MainLayout.FirstLayout>
          <Widgets />
        </MainLayout.FirstLayout>
      );
    case "extensions":
      return (
        <MainLayout.FirstLayout>
          <Extensions />
        </MainLayout.FirstLayout>
      );
    case "animation-settings":
      return (
        <MainLayout.FirstLayout>
          <AnimationSettings />
        </MainLayout.FirstLayout>
      );
    // `performance` is deliberately still routable even though the sidebar no
    // longer lists it — the screen moved into Animation Settings as a tab, and
    // people have the old URL bookmarked.
    case "performance":
      return (
        <MainLayout.FirstLayout>
          <Performance />
        </MainLayout.FirstLayout>
      );
    // Same arrangement as `performance`: the sidebar no longer lists it (the
    // Library screen became an Animation Settings tab) but the URL keeps
    // working for anyone who bookmarked it.
    case "integrations":
      return (
        <MainLayout.FirstLayout>
          <Integrations />
        </MainLayout.FirstLayout>
      );
    case "submissions":
      return (
        <MainLayout.FirstLayout>
          <Submissions />
        </MainLayout.FirstLayout>
      );
    case "stater-template":
      return (
        <MainLayout.SecondLayout>
          <StaterTemplate />
        </MainLayout.SecondLayout>
      );
    case "required-features":
      return (
        <MainLayout.ThirdLayout>
          <RequiredFeatures />
        </MainLayout.ThirdLayout>
      );
    case "demo-importing":
      return (
        <MainLayout.ThirdLayout>
          <DemoImporting />
        </MainLayout.ThirdLayout>
      );
    case "complete-import":
      return (
        <MainLayout.ThirdLayout>
          <CompleteImport />
        </MainLayout.ThirdLayout>
      );
    case "fail-import":
      return (
        <MainLayout.ThirdLayout>
          <FailImport />
        </MainLayout.ThirdLayout>
      );
    default:
      return (
        <MainLayout.FirstLayout>
          <Dashboard />
        </MainLayout.FirstLayout>
      );
  }
};
