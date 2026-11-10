import * as LabelPrimitive from "@radix-ui/react-label"
import * as React from "react"

import { cn } from "@/lib/utils"

/**
 * `required` adds the asterisk after the text (D-118). It is hidden from assistive tech; the
 * control itself carries `aria-required` (or `required`), so the state is announced once.
 * Fields without it are optional and carry no marker.
 */
function Label({
  className,
  children,
  required = false,
  ...props
}: React.ComponentProps<typeof LabelPrimitive.Root> & { required?: boolean }) {
  return (
    <LabelPrimitive.Root
      data-slot="label"
      className={cn(
        "text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50",
        className
      )}
      {...props}
    >
      {children}
      {required && <RequiredMark />}
    </LabelPrimitive.Root>
  )
}

/**
 * The asterisk on its own, for titles that are not a <Label> (a fieldset <legend>). Pair it with
 * `aria-required` on the group (`role="radiogroup"`) or on each control. Groups where
 * `aria-required` is not allowed (a set of checkboxes or chips) pass `srLabel` instead, so screen
 * readers hear "required" once.
 */
function RequiredMark({ srLabel }: { srLabel?: string }) {
  return (
    <>
      <span aria-hidden="true" data-slot="required-marker" className="ms-0.5 text-bad">
        *
      </span>
      {srLabel && <span className="sr-only"> ({srLabel})</span>}
    </>
  )
}

export { Label, RequiredMark }
