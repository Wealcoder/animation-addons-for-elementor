import { cn } from "@/lib/utils";

/**
 * Two (or more) columns that collapse to a single column below `lg`.
 *
 * `columns` is a list of fr weights, so [60, 40] reads as the 60/40 split the
 * dashboard uses throughout. The template is passed as a CSS variable rather
 * than an arbitrary Tailwind class so the dashboard's JIT build does not have
 * to know every ratio ahead of time.
 */
const SplitRow = ({
  columns = [50, 50],
  gap = "gap-6",
  className,
  children,
}) => {
  const template = columns.map((col) => `minmax(0, ${col}fr)`).join(" ");

  return (
    <div
      className={cn(
        "grid grid-cols-1 lg:[grid-template-columns:var(--split-cols)]",
        gap,
        className,
      )}
      style={{ "--split-cols": template }}
    >
      {children}
    </div>
  );
};

export default SplitRow;
