import { useEffect, useState } from "react";

const DEFAULT_TIMEOUT = 8000;

/**
 * Is this endpoint on the site we are running in?
 *
 * It decides whether the local admin nonce may be attached. Sending
 * `X-WP-Nonce` to a third-party host would hand it our nonce AND turn a
 * simple GET into a CORS preflight, so cross-origin requests go out bare.
 */
const isSameOrigin = (endpoint) => {
  try {
    return new URL(endpoint, window.location.href).origin === window.location.origin;
  } catch {
    return false;
  }
};

/**
 * Did the endpoint give us something worth showing?
 *
 * A WP REST error is a perfectly well-formed JSON object (`{code, message}`),
 * so "the request succeeded" is not the question — "is this renderable" is.
 * Anything that fails here leaves the fallback on screen.
 */
const isUsable = (value, fallback) => {
  if (Array.isArray(fallback)) {
    return Array.isArray(value) && value.length > 0;
  }

  return value !== null && value !== undefined;
};

const readCache = (key, ttl) => {
  if (!key) return null;

  try {
    const raw = window.sessionStorage.getItem(key);
    if (!raw) return null;

    const { at, value } = JSON.parse(raw);
    if (!at || Date.now() - at > ttl) return null;

    return value;
  } catch {
    return null;
  }
};

const writeCache = (key, value) => {
  if (!key) return;

  try {
    window.sessionStorage.setItem(
      key,
      JSON.stringify({ at: Date.now(), value }),
    );
  } catch {
    // Private mode, quota, or storage switched off — the cache is an
    // optimisation, never a requirement.
  }
};

/**
 * Returns fallback data when no endpoint is configured yet.
 * Once an endpoint is provided, it fetches from it and shows only the
 * fetched result (falling back only if that request fails).
 *
 * @param {string|null} endpoint  Absolute or site-relative URL, or null.
 * @param {*}           fallback  Rendered until (and unless) the fetch lands.
 * @param {object}      [options]
 * @param {Function}    [options.transform]  Maps the raw payload onto the
 *                                           shape the card renders.
 * @param {string}      [options.cacheKey]   sessionStorage key; omit to skip
 *                                           caching entirely.
 * @param {number}      [options.cacheTtl]   Cache lifetime in ms.
 * @param {number}      [options.timeout]    Abort after this many ms.
 */
export const useRemoteData = (endpoint, fallback, options = {}) => {
  const {
    transform,
    cacheKey,
    cacheTtl = 30 * 60 * 1000,
    timeout = DEFAULT_TIMEOUT,
  } = options;

  // Read the cache once, not on every render — JSON.parse is not free.
  const [seed] = useState(() => {
    if (!endpoint) return { value: fallback, warm: false };

    const cached = readCache(cacheKey, cacheTtl);
    return isUsable(cached, fallback)
      ? { value: cached, warm: true }
      : { value: fallback, warm: false };
  });

  const [data, setData] = useState(seed.value);
  // Only "loading" when there is a request to wait for AND nothing good to
  // show yet — a warm cache paints the real list on the first frame.
  const [loading, setLoading] = useState(Boolean(endpoint) && !seed.warm);

  useEffect(() => {
    if (!endpoint) {
      setData(fallback);
      setLoading(false);
      return;
    }

    if (seed.warm) return;

    let cancelled = false;
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), timeout);

    setLoading(true);

    const headers = {};
    if (isSameOrigin(endpoint)) {
      headers["X-WP-Nonce"] = WCF_ADDONS_ADMIN.nonce;
    }

    fetch(endpoint, {
      headers,
      // The remote answers with `Access-Control-Allow-Credentials: true` and a
      // reflected origin, so be explicit that we are not a logged-in caller.
      credentials: isSameOrigin(endpoint) ? "same-origin" : "omit",
      signal: controller.signal,
    })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then((json) => {
        if (cancelled) return;

        const value = transform ? transform(json) : json;
        if (!isUsable(value, fallback)) throw new Error("unusable payload");

        setData(value);
        writeCache(cacheKey, value);
      })
      .catch(() => {
        if (!cancelled) setData(fallback);
      })
      .finally(() => {
        window.clearTimeout(timer);
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
      controller.abort();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [endpoint]);

  return { data, loading };
};
