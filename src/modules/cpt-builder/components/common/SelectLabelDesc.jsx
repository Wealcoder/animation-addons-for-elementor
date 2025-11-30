import { cn } from "@/lib/utils";
import { Label } from "../ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../ui/select";

const SelectLabelDesc = ({
  className,
  selectTriggerClassName,
  selectContentClassName,
  labelClassName,
  descriptionClassName,
  label,
  placeholder,
  description,
  uniqId,
  selectItems,
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
      <Select
        id={uniqId}
        value={value}
        onValueChange={(value) => valueChange(value, changeableValueName)}
      >
        <SelectTrigger className={cn(selectTriggerClassName)}>
          <SelectValue placeholder={placeholder} />
        </SelectTrigger>
        <SelectContent className={cn(selectContentClassName)}>
          {selectItems?.map((item) => (
            <SelectItem key={uniqId + item.key} value={item.key}>
              {item.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      {description && (
        <small className={cn(descriptionClassName)}>{description}</small>
      )}
    </div>
  );
};

export default SelectLabelDesc;
