import AffiliateProgram from "@/components/dashboard/AffiliateProgram";
import Documentation from "@/components/dashboard/Documentation";
import HeroBanner from "@/components/dashboard/HeroBanner";
import LatestBlog from "@/components/dashboard/LatestBlog";
import RequestFeatureForm from "@/components/dashboard/RequestFeatureForm";
import Tutorial from "@/components/dashboard/Tutorial";
import VideoBanner from "@/components/dashboard/VideoBanner";
import WhatsNew from "@/components/dashboard/WhatsNew";
import SplitRow from "@/components/shared/SplitRow";
import AtomicOptInNotice from "@/components/shared/AtomicOptInNotice";
import AtomicUndoNotice from "@/components/shared/AtomicUndoNotice";

const Dashboard = () => {
  return (
    <div className="flex flex-col gap-6">
      {/*
       * Above the hero on purpose, and only ever while this site is mid-move to
       * Elementor V4 — see the components. Both render null the rest of the
       * time, so this changes nothing for every other site.
       */}
      <AtomicOptInNotice />
      <AtomicUndoNotice />

      <SplitRow columns={[60, 40]}>
        <HeroBanner />
        <VideoBanner />
      </SplitRow>

      <SplitRow columns={[60, 40]}>
        <Tutorial />
        <Documentation />
      </SplitRow>

      <SplitRow columns={[60, 40]}>
        <div className="flex flex-col gap-6">
          <AffiliateProgram />
          <WhatsNew />
        </div>
        <RequestFeatureForm />
      </SplitRow>

      <LatestBlog />
    </div>
  );
};

export default Dashboard;
