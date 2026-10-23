import { categoryColorClass } from '@/lib/category-colors';
import { iconComponent } from '@/lib/icons';
import { cn } from '@/lib/utils';

export function EntityIcon({
    name,
    color,
    className,
}: {
    name: string | null;
    color?: string | null;
    className?: string;
}) {
    const Icon = iconComponent(name);

    if (!Icon) {
        return null;
    }

    return (
        <span
            className={cn(
                'inline-flex size-8 shrink-0 items-center justify-center rounded-md text-foreground',
                categoryColorClass(color) ?? 'bg-muted',
                className,
            )}
            aria-hidden="true"
        >
            <Icon className="size-4 text-foreground" />
        </span>
    );
}
