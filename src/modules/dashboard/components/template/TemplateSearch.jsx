import { useState, useRef, useEffect, useCallback } from "react";
import { Search, X } from "lucide-react";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";

const TemplateSearch = ({ setMetaData }) => {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState([]);
  const [isOpen, setIsOpen] = useState(false);
  const [selectedIndex, setSelectedIndex] = useState(-1);
  const [isLoading, setIsLoading] = useState(false);
  const inputRef = useRef(null);
  const dropdownRef = useRef(null);
  const debounceTimerRef = useRef(null);
  const justSelectedRef = useRef(false);
  const isSelectedValueRef = useRef(false);

  // Debounced search function
  const debouncedSearch = useCallback((searchQuery) => {
    // Clear previous timer
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }

    // Set new timer
    debounceTimerRef.current = setTimeout(() => {
      if (searchQuery.trim() === "") {
        setResults([]);
        setIsOpen(false);
        setIsLoading(false);
        return;
      }

      setIsLoading(true);

      fetch(
        `https://www.themecrowdy.com/wp-json/wp/v2/starter-templates?page=1&per_page=50&title-search=yes&s=${encodeURIComponent(
          searchQuery.toLowerCase()
        )}`
      )
        .then((response) => response.json())
        .then((data) => {
          setResults(data?.templates || []);
          setIsOpen((data?.templates?.length || 0) > 0);
          setSelectedIndex(-1);
          setIsLoading(false);
        })
        .catch((error) => {
          console.error("Search error:", error);
          setResults([]);
          setIsOpen(false);
          setIsLoading(false);
        });
    }, 300); // 300ms delay
  }, []);

  // Handle query changes with debouncing
  useEffect(() => {
    // Don't search if this is a selected value
    if (isSelectedValueRef.current) {
      isSelectedValueRef.current = false;
      return;
    }

    debouncedSearch(query);

    // Cleanup timer on unmount
    return () => {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
    };
  }, [query, debouncedSearch]);

  // Handle keyboard navigation
  const handleKeyDown = (e) => {
    if (!isOpen) return;

    switch (e.key) {
      case "ArrowDown":
        e.preventDefault();
        setSelectedIndex((prev) =>
          prev < results.length - 1 ? prev + 1 : prev
        );
        break;
      case "ArrowUp":
        e.preventDefault();
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : -1));
        break;
      case "Enter":
        e.preventDefault();
        if (selectedIndex >= 0) {
          handleSelectResult(results[selectedIndex]);
        } else {
          handleSelectResult({ title: query });
        }
        break;
      case "Escape":
        setIsOpen(false);
        setSelectedIndex(-1);
        inputRef.current?.blur();
        break;
    }
  };

  // Handle result selection
  const handleSelectResult = (result) => {
    justSelectedRef.current = true; // Mark that we just selected something
    isSelectedValueRef.current = true; // Mark that next query change is from selection
    setQuery(result.title);
    console.log(result.title);
    setMetaData((prev) => ({ ...prev, searchKey: result.title }));
    setIsOpen(false);
    setSelectedIndex(-1);

    // Reset the flag after a short delay
    setTimeout(() => {
      justSelectedRef.current = false;
    }, 100);
  };

  // Clear search
  const handleClear = () => {
    setQuery("");
    setResults([]);
    setIsOpen(false);
    setSelectedIndex(-1);
    isSelectedValueRef.current = false; // Reset the selected value flag
    inputRef.current?.focus();
    setMetaData((prev) => ({ ...prev, searchKey: "" }));
  };

  // Handle input focus - only open if we didn't just select something
  const handleFocus = () => {
    if (!justSelectedRef.current && results.length > 0) {
      setIsOpen(true);
    }
  };

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target) &&
        !inputRef.current?.contains(event.target)
      ) {
        setIsOpen(false);
        setSelectedIndex(-1);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="relative w-full max-w-[400px]">
      {/* Search Input Container */}
      <div className="relative py-[11px]">
        {/* Search Icon or Clear Button */}
        {query ? (
          <button
            onClick={handleClear}
            className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-[#202020] hover:text-[#F6502C] transition-colors bg-white"
            aria-label="Clear search"
          >
            <X className="h-4 w-4" />
          </button>
        ) : (
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-[#797979] pointer-events-none" />
        )}

        {/* Input Field */}
        <Input
          ref={inputRef}
          type="text"
          placeholder="Search Templates...."
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={handleKeyDown}
          onFocus={handleFocus}
          className="pl-10 pr-10 h-11 text-base border-[#00000026] focus:border-[#202020] focus:ring-0 placeholder:text-[#797979]"
        />

        {/* Loading indicator */}
        {isLoading && (
          <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
            <div className="animate-spin rounded-full h-4 w-4 border-2 border-[#F6502C] border-t-transparent"></div>
          </div>
        )}
      </div>

      {/* Search Results Dropdown */}
      {isOpen && results.length > 0 && (
        <div
          ref={dropdownRef}
          className="absolute top-full left-0 right-0 mt-1 bg-[#FFFFFF] border border-[#1212121A] rounded-[10px] z-50 max-h-80 overflow-y-auto px-[30px] py-5 shadow-lg"
        >
          {results.map((result, index) => (
            <div
              key={result.id}
              onClick={() => handleSelectResult(result)}
              className={cn(
                "w-full py-2.5 first:pt-0 text-left text-[#202020] hover:text-[#F6502C] transition-colors cursor-pointer",
                selectedIndex === index && "text-[#F6502C]"
              )}
            >
              <p className="text-sm font-medium">{result.title}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default TemplateSearch;
