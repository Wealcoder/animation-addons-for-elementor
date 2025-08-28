import { cn } from "@/lib/utils";
import { Label } from "../ui/label";
import { Switch } from "../ui/switch";

const SwitchLabelDesc = ({
  className,
  switchClassName,
  labelClassName,
  descriptionClassName,
  label,
  description,
  uniqId,
  value,
  valueChange,
  changeableValueName,
}) => {
  return (
    <div className={cn("flex items-start space-x-2", className)}>
      <Switch
        id={uniqId}
        checked={value}
        onCheckedChange={(value) => valueChange(value, changeableValueName)}
        className={cn(switchClassName)}
      />
      <div className="flex flex-col gap-1">
        <Label htmlFor={uniqId} className={cn(labelClassName)}>
          {label}
        </Label>
        {description && (
          <small className={cn(descriptionClassName)}>{description}</small>
        )}
      </div>
    </div>
  );
};

export default SwitchLabelDesc;
