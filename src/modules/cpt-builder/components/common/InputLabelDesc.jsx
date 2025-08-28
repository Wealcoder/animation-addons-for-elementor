import { cn } from "@/lib/utils";
import { Input } from "../ui/input";
import { Label } from "../ui/label";

const InputLabelDesc = ({
  className,
  inputClassName,
  labelClassName,
  descriptionClassName,
  label,
  placeholder,
  description,
  uniqId,
  value,
  valueChange,
  changeableValueName,
}) => {
  return (
    <div className={cn("grid w-full max-w-lg items-center gap-2", className)}>
      {label && (
        <Label htmlFor={uniqId} className={cn(labelClassName)}>
          {label}
        </Label>
      )}
      <Input
        id={uniqId}
        placeholder={placeholder}
        value={value}
        onChange={(e) => valueChange(e.target.value, changeableValueName)}
        className={cn(inputClassName)}
      />
      {description && (
        <small className={cn(descriptionClassName)}>{description}</small>
      )}
    </div>
  );
};

export default InputLabelDesc;
