import { useCallback, useEffect, useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "S/components/ui/dialog";
import { Button } from "S/components/ui/button";
import { Textarea } from "S/components/ui/textarea";
import { Input } from "S/components/ui/input";
import { Label } from "S/components/ui/label";
import { Checkbox } from "S/components/ui/checkbox";
import { Badge } from "S/components/ui/badge";
import { usePostType, useTab } from "S/hooks/app.hooks";
import { debounceFn } from "S/lib/utils";
import { applyPostType } from "S/lib/apply-post-type";

const SUPPORTS = [
  "title",
  "author",
  "comments",
  "trackbacks",
  "editor",
  "excerpt",
  "revisions",
  "page-attributes",
  "thumbnail",
  "custom-fields",
  "post-formats",
];

async function ajax(body) {
  const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: new URLSearchParams(body),
  });
  return response.json();
}

const AiDescribeModal = ({ open, onOpenChange }) => {
  const { updateTabKey } = useTab();
  const { setAllPostTypes } = usePostType();
  const ai = WCF_ADDONS_ADMIN.ai || { available: false, configured: false };

  const [description, setDescription] = useState("");
  const [busy, setBusy] = useState(false);
  const [applying, setApplying] = useState(false);
  const [error, setError] = useState("");
  const [proposal, setProposal] = useState(null);
  const [keyTaken, setKeyTaken] = useState(false);

  useEffect(() => {
    if (!open) {
      setDescription("");
      setBusy(false);
      setApplying(false);
      setError("");
      setProposal(null);
      setKeyTaken(false);
    }
  }, [open]);

  const checkKey = useCallback(
    debounceFn(async (value) => {
      try {
        const r = await ajax({
          action: "aaeaddon_post_type_exist",
          post_type: value,
          nonce: WCF_ADDONS_ADMIN.nonce,
        });
        setKeyTaken(!!r?.hasExist);
      } catch (e) {
        console.log(e);
      }
    }),
    []
  );

  const generate = async () => {
    setBusy(true);
    setError("");
    setProposal(null);
    try {
      const r = await ajax({
        action: "aaeaddon_cpt_ai_propose",
        description,
        nonce: WCF_ADDONS_ADMIN.nonce,
      });
      if (!r?.success) {
        setError(
          (typeof r?.data === "string" ? r.data : r?.data?.message) ||
            "The AI did not answer."
        );
        return;
      }
      setProposal(r.data);
      setKeyTaken(!!r.data.post_type_exists);
    } catch (e) {
      setError("Something went wrong talking to the AI.");
    } finally {
      setBusy(false);
    }
  };

  const updateProposal = (patch) =>
    setProposal((prev) => ({ ...prev, ...patch }));

  const toggleSupport = (value) => {
    const supports = proposal.supports || [];
    updateProposal({
      supports: supports.includes(value)
        ? supports.filter((s) => s !== value)
        : [...supports, value],
    });
  };

  const use = async () => {
    setApplying(true);
    try {
      await applyPostType({
        pluralLabel: proposal.plural_label,
        meta: {
          singular_name: proposal.singular_name,
          post_type_key: proposal.post_type_key,
          taxonomies: proposal.taxonomies_existing || [],
          public: !!proposal.public,
          hierarchical: !!proposal.hierarchical,
          active: true,
          advance: true,
          supports: proposal.supports || [],
          description: proposal.description || "",
        },
        setAllPostTypes,
        updateTabKey,
      });
      onOpenChange(false);
    } catch (e) {
      setError("Could not create the post type.");
      setApplying(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-xl">
        <DialogHeader>
          <DialogTitle>Describe it to AI</DialogTitle>
          <DialogDescription>
            Describe the content, and a post type will be proposed. Nothing is
            created until you press "Use this".
          </DialogDescription>
        </DialogHeader>

        {!ai.available ? (
          <p className="text-sm">
            This needs Animation Addons Pro, active, with an AI provider
            configured.
          </p>
        ) : !ai.configured ? (
          <p className="text-sm">
            Add an API key first.
            {ai.settings ? (
              <>
                {" "}
                <a className="text-brand underline" href={ai.settings}>
                  Open AI &amp; Connections
                </a>
              </>
            ) : null}
          </p>
        ) : (
          <div className="flex flex-col gap-3">
            <Textarea
              rows={3}
              placeholder="e.g. A portfolio of client case studies with a project date and an industry category."
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
            <Button
              onClick={generate}
              disabled={busy || description.trim().length < 8}
            >
              {busy ? "Thinking…" : "Generate"}
            </Button>
            {error ? <p className="text-xs text-brand">{error}</p> : null}

            {proposal ? (
              <div className="border rounded-lg p-4 flex flex-col gap-3 max-h-[50vh] overflow-y-auto">
                <div className="grid grid-cols-2 gap-3">
                  <div className="grid gap-1">
                    <Label>Plural label</Label>
                    <Input
                      value={proposal.plural_label || ""}
                      onChange={(e) =>
                        updateProposal({ plural_label: e.target.value })
                      }
                    />
                  </div>
                  <div className="grid gap-1">
                    <Label>Singular label</Label>
                    <Input
                      value={proposal.singular_name || ""}
                      onChange={(e) =>
                        updateProposal({ singular_name: e.target.value })
                      }
                    />
                  </div>
                </div>
                <div className="grid gap-1">
                  <Label>Post type key</Label>
                  <Input
                    value={proposal.post_type_key || ""}
                    onChange={(e) => {
                      const value = e.target.value.replace(/\s+/g, "");
                      updateProposal({ post_type_key: value });
                      checkKey(value);
                    }}
                  />
                  {keyTaken ? (
                    <small className="text-brand">
                      That post type key already exists - change it before
                      continuing.
                    </small>
                  ) : null}
                </div>
                <div>
                  <Label>Supports</Label>
                  <div className="grid grid-cols-3 gap-y-1 gap-x-3 mt-1">
                    {SUPPORTS.map((s) => (
                      <label key={s} className="flex items-center gap-2 text-sm">
                        <Checkbox
                          checked={(proposal.supports || []).includes(s)}
                          onCheckedChange={() => toggleSupport(s)}
                        />
                        {s}
                      </label>
                    ))}
                  </div>
                </div>
                {proposal.taxonomies_existing?.length ? (
                  <div className="flex flex-wrap gap-1">
                    {proposal.taxonomies_existing.map((tx) => (
                      <Badge key={tx} variant="secondary">
                        {tx}
                      </Badge>
                    ))}
                  </div>
                ) : null}
                {proposal.taxonomy_suggestion ? (
                  <p className="text-xs text-muted-foreground">
                    Suggested taxonomy: {proposal.taxonomy_suggestion} (create
                    it separately in the Taxonomy Builder)
                  </p>
                ) : null}
                {proposal.rationale ? (
                  <p className="text-xs italic text-muted-foreground">
                    {proposal.rationale}
                  </p>
                ) : null}
                <Button
                  onClick={use}
                  disabled={applying || keyTaken || !proposal.post_type_key}
                >
                  {applying ? "Creating…" : "Use this"}
                </Button>
              </div>
            ) : null}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
};

export default AiDescribeModal;
