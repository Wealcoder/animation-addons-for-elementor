import TutorialThumb01 from "../../../../../public/images/tutorials/tutorial-thumb-01.png";
import TutorialThumb02 from "../../../../../public/images/tutorials/tutorial-thumb-02.png";
import TutorialThumb03 from "../../../../../public/images/tutorials/tutorial-thumb-03.png";

/**
 * The Tutorial card's three rows.
 *
 * `videoUrl` is the short youtu.be form without the `?si=` share token:
 * VideoDialog's toYoutubeEmbedUrl() rebuilds the embed from the id alone, so
 * the token would be stripped anyway and only makes these lines harder to
 * diff against the real channel.
 *
 * Note API_ENDPOINTS.tutorials is null, so useRemoteData never fetches over
 * this — the list below IS the shipped content, not a fallback. Titles and
 * durations must match the actual clips.
 */
export const TutorialList = [
  {
    title: "Create Stunning Animated Websites in WordPress",
    duration: "2 min 02 sec",
    thumbnail: TutorialThumb01,
    videoUrl: "https://youtu.be/tRbvgq2gJF4",
  },
  {
    title: "Build Your Dream Website in Minutes With No Coding Needed!",
    duration: "1 min 55 sec",
    thumbnail: TutorialThumb02,
    videoUrl: "https://youtu.be/wb-PNFmKpGc",
  },
  {
    title: "Best WordPress Animation Plugin for GSAP Animations Without Coding",
    duration: "2 min 00 sec",
    thumbnail: TutorialThumb03,
    videoUrl: "https://youtu.be/z4Y3xbsLo0M",
  },
];
