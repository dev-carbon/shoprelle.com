import { cn } from '@/lib/utils';

/**
 * Status colours are named by the server (PurchaseRequestStatus::color) and
 * mapped to full class strings here, because Tailwind only keeps classes it can
 * see written out.
 *
 * A dot carries the colour and the text stays near-black: at a glance the
 * badges read as a calm column rather than a row of highlighter marks.
 */
const DOT_CLASSES: Record<string, string> = {
    blue: 'bg-blue-500',
    amber: 'bg-amber-500',
    green: 'bg-green-500',
    cyan: 'bg-cyan-600',
    violet: 'bg-violet-500',
    emerald: 'bg-emerald-600',
    red: 'bg-red-500',
};

export function StatusBadge({
    label,
    color,
    className,
}: {
    label: string;
    color: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex w-fit shrink-0 items-center gap-1.5 rounded-md border bg-card py-1 pr-2.5 pl-2 text-xs font-medium whitespace-nowrap text-foreground',
                className,
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'size-1.5 rounded-full',
                    DOT_CLASSES[color] ?? DOT_CLASSES.blue,
                )}
            />
            {label}
        </span>
    );
}
