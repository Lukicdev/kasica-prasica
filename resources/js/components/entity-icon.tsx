import { iconComponent } from '@/lib/icons';
import { cn } from '@/lib/utils';

export function EntityIcon({
    name,
    className,
}: {
    name: string | null;
    className?: string;
}) {
    const Icon = iconComponent(name);

    if (!Icon) {
        return null;
    }

    return (
        <span
            className={cn(
                'inline-flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-foreground',
                className,
            )}
            aria-hidden="true"
        >
            <Icon className="size-4" />
        </span>
    );
}
