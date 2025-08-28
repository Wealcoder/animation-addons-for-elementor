import {
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table";
import { ArrowUpDown, ChevronDown, MoreHorizontal } from "lucide-react";
import { useState } from "react";
import DeleteConfirmDialog from "S/components/common/DeleteConfirmDialog";

import { Button } from "S/components/ui/button";
import { Checkbox } from "S/components/ui/checkbox";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "S/components/ui/dropdown-menu";
import { Input } from "S/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "S/components/ui/table";
import { useTab, useTaxonomy } from "S/hooks/app.hooks";
import { generateLabelValue } from "S/lib/utils";

const ShowAllTaxonomy = () => {
  const { updateTabKey } = useTab();
  const { allTaxonomy, setAllTaxonomy } = useTaxonomy();

  const [sorting, setSorting] = useState([]);
  const [columnFilters, setColumnFilters] = useState([]);
  const [columnVisibility, setColumnVisibility] = useState({});
  const [rowSelection, setRowSelection] = useState({});

  const changeRoute = (value, id) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");
    url.hash = "";
    url.search = `page=${pageQuery}`;
    url.searchParams.set("tab", value);
    url.searchParams.set("taxonomyId", id);
    window.history.replaceState({}, "", url);

    updateTabKey(value);
  };

  const deleteTaxonomy = async (value) => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_delete_taxonomy_builder",
          taxonomy_id: value,
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          console.log(return_content?.data);
          setAllTaxonomy(return_content?.data);
        });
    } catch (error) {
      console.log(error);
    }
  };

  const columns = [
    {
      id: "select",
      header: ({ table }) => (
        <Checkbox
          checked={
            table.getIsAllPageRowsSelected() ||
            (table.getIsSomePageRowsSelected() && "indeterminate")
          }
          onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
          aria-label="Select all"
        />
      ),
      cell: ({ row }) => (
        <Checkbox
          checked={row.getIsSelected()}
          onCheckedChange={(value) => row.toggleSelected(!!value)}
          aria-label="Select row"
        />
      ),
      enableSorting: false,
      enableHiding: false,
    },

    {
      accessorKey: "post_title",
      header: ({ column }) => {
        return (
          <Button
            variant="link"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
            className="gap-3 px-0"
          >
            Title
            <ArrowUpDown size={16} />
          </Button>
        );
      },
      cell: ({ row }) => (
        <div
          className="cursor-pointer hover:text-brand"
          onClick={() => changeRoute("taxonomy-edit", row.original?.ID)}
        >
          {row.getValue("post_title")}
        </div>
      ),
    },
    {
      accessorKey: "meta_data",
      header: "Post Types",
      cell: ({ row }) => {
        const result = generateLabelValue(allTaxonomy?.post_types)
          ?.filter((item) =>
            row.getValue("meta_data")?.post_types?.includes(item.value)
          )
          .map((item) => item.label)
          .join(", ");

        return <div>{result}</div>;
      },
    },
    {
      accessorKey: "meta_data",
      header: "Status",
      cell: ({ row }) => (
        <div className="capitalize">
          {row.getValue("meta_data").active ? `Active` : `Inactive`}
        </div>
      ),
    },

    {
      id: "actions",
      enableHiding: false,
      header: "Action",
      cell: ({ row }) => {
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="link" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem
                onClick={() => changeRoute("taxonomy-edit", row.original?.ID)}
              >
                Edit Taxonomy Title
              </DropdownMenuItem>

              <DropdownMenuItem onClick={(e) => e.preventDefault()}>
                <DeleteConfirmDialog
                  deleteFn={deleteTaxonomy}
                  id={row.original?.ID}
                >
                  <div>Delete Taxonomy</div>
                </DeleteConfirmDialog>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  const table = useReactTable({
    data: allTaxonomy?.data || [],
    columns,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onColumnVisibilityChange: setColumnVisibility,
    onRowSelectionChange: setRowSelection,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      rowSelection,
    },
  });

  return (
    <div className="w-full">
      <div className="flex items-center py-4">
        <Input
          placeholder="Filter Post Title..."
          value={table.getColumn("post_title")?.getFilterValue() ?? ""}
          onChange={(event) =>
            table.getColumn("post_title")?.setFilterValue(event.target.value)
          }
          className="max-w-sm"
        />
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="secondary" className="ml-auto">
              Columns <ChevronDown />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            {table
              .getAllColumns()
              .filter((column) => column.getCanHide())
              .map((column) => {
                return (
                  <DropdownMenuCheckboxItem
                    key={column.id}
                    className="capitalize"
                    checked={column.getIsVisible()}
                    onCheckedChange={(value) =>
                      column.toggleVisibility(!!value)
                    }
                  >
                    {column.id}
                  </DropdownMenuCheckboxItem>
                );
              })}
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  return (
                    <TableHead key={header.id}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  );
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="h-24 text-center"
                >
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
      <div className="flex items-center justify-end space-x-2 py-4">
        <div className="flex-1 text-sm text-muted-foreground">
          {table.getFilteredSelectedRowModel().rows.length} of{" "}
          {table.getFilteredRowModel().rows.length} row(s) selected.
        </div>
        <div className="space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
};

export default ShowAllTaxonomy;
