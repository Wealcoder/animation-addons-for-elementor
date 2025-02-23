import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";

const TemplatePagination = () => {
  return (
    <Pagination className={"justify-end"}>
      <PaginationContent>
        <PaginationItem>
          <PaginationPrevious
            href="#"
            className={"border-0 hover:bg-transparent shadow-none"}
          />
        </PaginationItem>
        <PaginationItem>
          <PaginationLink href="#" isActive>
            1
          </PaginationLink>
        </PaginationItem>
        <PaginationItem>
          <PaginationLink href="#">2</PaginationLink>
        </PaginationItem>
        <PaginationItem>
          <PaginationLink href="#">3</PaginationLink>
        </PaginationItem>
        <PaginationItem>
          <PaginationEllipsis />
        </PaginationItem>
        <PaginationItem>
          <PaginationLink href="#">8</PaginationLink>
        </PaginationItem>
        <PaginationItem>
          <PaginationNext
            href="#"
            className={"border-0 hover:bg-transparent shadow-none"}
          />
        </PaginationItem>
      </PaginationContent>
    </Pagination>
  );
};

export default TemplatePagination;
