import InfoNote from "@/components/shared/InfoToggle";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { __ } from "@wordpress/i18n";
import { useEffect, useRef, useState } from "react";

/**
 * Include/Exclude rules over locations, in the shape Elementor's theme builder
 * conditions use.
 *
 * Its own component rather than another branch of FeaturePanel's renderField()
 * because the "Specific pages" rows need hooks — a debounced search and a cache
 * of id → title. renderField is called conditionally, so hooks cannot live
 * there.
 *
 * Order does not matter: Exclude always beats Include, so there is no drag
 * handle to build and nothing to explain about precedence beyond that sentence.
 */

/** POST to admin-ajax and return `data`, or null on any failure. */
async function post(action, body) {
  if (!WCF_ADDONS_ADMIN?.ajaxurl || !WCF_ADDONS_ADMIN?.nonce) return null;

  try {
    const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      credentials: "same-origin",
      body: Object.entries({ action, nonce: WCF_ADDONS_ADMIN.nonce, ...body })
        .reduce((form, [key, value]) => {
          if (Array.isArray(value)) {
            value.forEach((v) => form.append(`${key}[]`, v));
          } else {
            form.append(key, value);
          }
          return form;
        }, new FormData()),
    });

    const json = await response.json();
    return json?.success ? json.data : null;
  } catch {
    return null;
  }
}

/**
 * The page picker for one `specific` rule: a search box, results to click, and
 * chips for what is already chosen.
 */
const PagePicker = ({ ids, disabled, onChange, titles, onResolve, testid }) => {
  const [term, setTerm] = useState("");
  const [results, setResults] = useState([]);
  const [open, setOpen] = useState(false);
  const timer = useRef(null);

  // Debounced: a keystroke-per-request search on a big site is a lot of
  // queries to throw away.
  useEffect(() => {
    if (!term.trim()) {
      setResults([]);
      return;
    }

    clearTimeout(timer.current);
    timer.current = setTimeout(async () => {
      const data = await post("aae_search_content", { search: term });
      setResults(data?.results || []);
      setOpen(true);
    }, 300);

    return () => clearTimeout(timer.current);
  }, [term]);

  const add = (item) => {
    onResolve(item);
    if (!ids.includes(item.id)) onChange([...ids, item.id]);
    setTerm("");
    setResults([]);
    setOpen(false);
  };

  return (
    <div className="mt-2 ml-[138px]">
      <div className="flex flex-wrap gap-1.5">
        {ids.map((id) => (
          <span
            className="inline-flex items-center gap-1.5 bg-[#F5F7FA] border border-[#E1E4EA] rounded px-2 py-1 text-[12px]"
            key={id}
          >
            {/* Falls back to the id while the title is still being fetched, and
                permanently if the post was deleted — which is the useful signal
                that the rule is now pointing at nothing. */}
            {titles[id] || `#${id}`}
            <button
              type="button"
              className="text-[var(--600,#525866)] leading-none"
              disabled={disabled}
              aria-label={__("Remove", "animation-addons-for-elementor")}
              onClick={() => onChange(ids.filter((i) => i !== id))}
            >
              ×
            </button>
          </span>
        ))}
      </div>

      <div className="relative mt-2">
        <Input
          className="h-9"
          placeholder={__(
            "Search pages or posts…",
            "animation-addons-for-elementor",
          )}
          disabled={disabled}
          value={term}
          data-aae-field={testid}
          onChange={(e) => setTerm(e.target.value)}
          onFocus={() => results.length && setOpen(true)}
        />

        {open && !!results.length && (
          <ul className="absolute z-20 left-0 right-0 mt-1 max-h-56 overflow-auto rounded-md border border-[#E1E4EA] bg-white shadow-lg">
            {results.map((item) => (
              <li key={item.id}>
                <button
                  type="button"
                  className="w-full text-left px-3 py-2 text-[13px] hover:bg-[#F5F7FA] flex items-center justify-between gap-3"
                  data-aae-result={item.id}
                  onClick={() => add(item)}
                >
                  <span className="truncate">{item.title}</span>
                  <span className="text-[11px] text-[var(--600,#525866)] shrink-0">
                    {item.type}
                  </span>
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
};

const ConditionsField = ({ field, feature, name, value, disabled, onChange }) => {
  const rules = Array.isArray(value) ? value : [];
  const locations = Object.entries(field.locations || {});

  // id → title, shared across rows so the same page picked twice is fetched
  // once.
  const [titles, setTitles] = useState({});

  // Saved rules arrive as bare ids. Resolve them once so the chips read as page
  // names rather than numbers the user has to go and look up.
  useEffect(() => {
    const unknown = rules
      .flatMap((rule) => rule.ids || [])
      .filter((id) => !titles[id]);

    if (!unknown.length) return;

    let live = true;

    post("aae_search_content", { ids: [...new Set(unknown)] }).then((data) => {
      if (!live || !data?.results?.length) return;

      setTitles((prev) => ({
        ...prev,
        ...Object.fromEntries(data.results.map((r) => [r.id, r.title])),
      }));
    });

    return () => {
      live = false;
    };
  }, [value]);

  const patch = (index, change) =>
    onChange(rules.map((r, i) => (i === index ? { ...r, ...change } : r)));

  const remember = (item) =>
    setTitles((prev) => ({ ...prev, [item.id]: item.title }));

  return (
    <div className="mt-6">
      <InfoNote label={field.label} testid={`${feature}.${name}.help`}>
        {field.help}
      </InfoNote>

      <div className="mt-3 space-y-3">
        {rules.map((rule, index) => (
          // Index as key is safe only because rows are never reordered — add
          // and remove are the whole vocabulary.
          <div key={index}>
            <div className="flex items-center gap-2">
              <Select
                value={rule.action || "include"}
                disabled={disabled}
                onValueChange={(next) => patch(index, { action: next })}
              >
                <SelectTrigger
                  className="h-10 w-[130px]"
                  data-aae-field={`${feature}.${name}.${index}.action`}
                >
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="include">
                    {__("Include", "animation-addons-for-elementor")}
                  </SelectItem>
                  <SelectItem value="exclude">
                    {__("Exclude", "animation-addons-for-elementor")}
                  </SelectItem>
                </SelectContent>
              </Select>

              <Select
                value={rule.location || ""}
                disabled={disabled}
                onValueChange={(next) =>
                  // Switching away from `specific` drops the id list rather
                  // than keeping it hidden — a stored list nobody can see is
                  // how a rule comes back different from how it looks.
                  patch(index, {
                    location: next,
                    ids: next === "specific" ? rule.ids || [] : undefined,
                  })
                }
              >
                <SelectTrigger
                  className="h-10 flex-1"
                  data-aae-field={`${feature}.${name}.${index}.location`}
                >
                  <SelectValue
                    placeholder={__("Where", "animation-addons-for-elementor")}
                  />
                </SelectTrigger>
                <SelectContent>
                  {locations.map(([slug, label]) => (
                    <SelectItem key={slug} value={slug}>
                      {label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Button
                variant="secondary"
                className="h-10 px-3"
                disabled={disabled}
                aria-label={__("Remove", "animation-addons-for-elementor")}
                data-aae-remove={`${feature}.${name}.${index}`}
                onClick={() => onChange(rules.filter((_, i) => i !== index))}
              >
                ×
              </Button>
            </div>

            {rule.location === "specific" && (
              <PagePicker
                ids={rule.ids || []}
                titles={titles}
                disabled={disabled}
                testid={`${feature}.${name}.${index}.search`}
                onResolve={remember}
                onChange={(ids) => patch(index, { ids })}
              />
            )}
          </div>
        ))}
      </div>

      {!rules.length && (
        <p className="text-[12px] text-[var(--600,#525866)] mt-2">
          {__("No rules — loads everywhere.", "animation-addons-for-elementor")}
        </p>
      )}

      <Button
        variant="secondary"
        className="h-9 mt-3"
        disabled={disabled}
        data-aae-add={`${feature}.${name}`}
        onClick={() =>
          onChange([...rules, { action: "include", location: "entire" }])
        }
      >
        {__("+ Add Condition", "animation-addons-for-elementor")}
      </Button>
    </div>
  );
};

export default ConditionsField;
