import { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "S/components/ui/dialog";
import { Button } from "S/components/ui/button";
import { Badge } from "S/components/ui/badge";
import { usePostType, useTab } from "S/hooks/app.hooks";
import { applyPostType } from "S/lib/apply-post-type";

const TemplatesModal = ({ open, onOpenChange }) => {
  const { updateTabKey } = useTab();
  const { setAllPostTypes } = usePostType();
  const [applyingId, setApplyingId] = useState(null);
  const templates = WCF_ADDONS_ADMIN.templates || [];

  const use = async (t) => {
    setApplyingId(t.id);
    try {
      await applyPostType({
        pluralLabel: t.plural_label,
        meta: t.meta,
        setAllPostTypes,
        updateTabKey,
      });
      onOpenChange(false);
    } catch (error) {
      console.log(error);
    } finally {
      setApplyingId(null);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Start from a template</DialogTitle>
          <DialogDescription>
            Pick a ready-made post type. You can still change anything
            afterwards in the edit screen.
          </DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto">
          {templates.map((t) => (
            <div
              key={t.id}
              className="border rounded-lg p-4 flex flex-col gap-2"
            >
              <div className="font-medium">{t.title}</div>
              <p className="text-xs text-muted-foreground flex-1">
                {t.description}
              </p>
              <div className="flex flex-wrap gap-1">
                {(t.meta?.supports || []).map((s) => (
                  <Badge key={s} variant="secondary">
                    {s}
                  </Badge>
                ))}
              </div>
              {t.suggested_taxonomy ? (
                <p className="text-xs text-muted-foreground">
                  Suggested taxonomy: {t.suggested_taxonomy} (create it
                  separately in the Taxonomy Builder)
                </p>
              ) : null}
              <Button
                size="sm"
                onClick={() => use(t)}
                disabled={applyingId !== null}
              >
                {applyingId === t.id ? "Creating…" : "Use this template"}
              </Button>
            </div>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default TemplatesModal;
