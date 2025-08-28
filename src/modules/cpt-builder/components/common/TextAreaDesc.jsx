import { cn } from "@/lib/utils";
import { Label } from "../ui/label";
import { Textarea } from "../ui/textarea";

const TextAreaDesc = ({
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
      <Textarea
        id={uniqId}
        placeholder={placeholder}
        className={cn(inputClassName)}
        value={value}
        onChange={(e) => valueChange(e.target.value, changeableValueName)}
      />
      {description && (
        <small className={cn(descriptionClassName)}>{description}</small>
      )}
    </div>
  );
};

export default TextAreaDesc;
