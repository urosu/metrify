import * as React from "react"
import { Search } from "lucide-react"
import { cn } from "@/lib/utils"

interface CommandProps {
  children: React.ReactNode;
  className?: string;
}

function Command({ children, className }: CommandProps) {
  return (
    <div
      data-slot="command"
      className={cn("flex h-full w-full flex-col overflow-hidden rounded-lg bg-popover text-popover-foreground", className)}
    >
      {children}
    </div>
  )
}

interface CommandInputProps extends React.ComponentProps<"input"> {
  placeholder?: string;
}

function CommandInput({ className, placeholder, ...props }: CommandInputProps) {
  return (
    <div className="flex items-center border-b px-3" data-slot="command-input-wrapper">
      <Search className="mr-2 h-4 w-4 shrink-0 text-muted-foreground/50" />
      <input
        data-slot="command-input"
        className={cn(
          "flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50",
          className,
        )}
        placeholder={placeholder}
        {...props}
      />
    </div>
  )
}

function CommandList({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <div
      data-slot="command-list"
      className={cn("max-h-72 overflow-y-auto overflow-x-hidden", className)}
    >
      {children}
    </div>
  )
}

function CommandEmpty({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <p data-slot="command-empty" className={cn("py-6 text-center text-sm text-muted-foreground", className)}>
      {children}
    </p>
  )
}

function CommandGroup({
  heading,
  children,
  className,
}: {
  heading?: string;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div data-slot="command-group" className={cn("overflow-hidden p-1", className)}>
      {heading && (
        <div className="px-2 py-1.5 text-xs font-medium text-muted-foreground">
          {heading}
        </div>
      )}
      {children}
    </div>
  )
}

function CommandItem({
  children,
  className,
  onSelect,
  disabled,
}: {
  children: React.ReactNode;
  className?: string;
  onSelect?: () => void;
  disabled?: boolean;
}) {
  return (
    <div
      data-slot="command-item"
      role="option"
      aria-disabled={disabled}
      onClick={disabled ? undefined : onSelect}
      className={cn(
        "relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none select-none",
        "hover:bg-accent hover:text-accent-foreground",
        disabled && "pointer-events-none opacity-50",
        className,
      )}
    >
      {children}
    </div>
  )
}

function CommandSeparator({ className }: { className?: string }) {
  return <div data-slot="command-separator" className={cn("-mx-1 h-px bg-border", className)} />
}

export {
  Command,
  CommandInput,
  CommandList,
  CommandEmpty,
  CommandGroup,
  CommandItem,
  CommandSeparator,
}
