import { Link } from '@inertiajs/react';
import { XIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';

export function FilterChip({
    icon,
    label,
    href,
    removeLabel,
    test,
}: {
    icon?: ReactNode;
    label: string;
    href: string;
    removeLabel: string;
    test?: string;
}) {
    return (
        <Badge variant="outline" className="h-7 px-2" data-test={test}>
            {icon}
            {label}
            <Link
                href={href}
                aria-label={removeLabel}
                className="inline-flex size-5 items-center justify-center rounded-sm"
            >
                <XIcon className="size-3" />
            </Link>
        </Badge>
    );
}
