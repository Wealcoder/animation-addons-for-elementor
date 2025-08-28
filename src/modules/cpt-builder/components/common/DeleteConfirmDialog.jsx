import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "../ui/dialog";
import { Button } from "../ui/button";
import { Trash2 } from "lucide-react";
import { cn } from "S/lib/utils";

const DeleteConfirmDialog = ({ className, deleteFn, id, children }) => {
  return (
    <Dialog>
      <DialogTrigger asChild>
        {children ?? (
          <div className={cn(className)}>
            <Trash2 />
          </div>
        )}
      </DialogTrigger>
      <DialogContent className="max-w-[300px]">
        <DialogHeader className={"hidden"}>
          <DialogTitle></DialogTitle>
          <DialogDescription></DialogDescription>
        </DialogHeader>
        <div>
          <p className="text-sm">Do you want to delete this</p>
        </div>
        <DialogFooter className={"sm:justify-start"}>
          <DialogClose asChild>
            <Button variant="secondary" onClick={() => deleteFn(id)}>
              Confirm
            </Button>
          </DialogClose>
          <DialogClose asChild>
            <Button>Cancel</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default DeleteConfirmDialog;
