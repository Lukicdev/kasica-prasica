import { Link } from '@inertiajs/react';

export function TableRowLink({
    href,
    label,
}: {
    href: React.ComponentProps<typeof Link>['href'];
    label?: string;
}) {
    const named = label !== undefined;

    return (
        <Link
            href={href}
            aria-label={label}
            aria-hidden={named ? undefined : true}
            tabIndex={named ? undefined : -1}
            className="absolute inset-0 z-10"
        />
    );
}
