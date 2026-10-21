import { EntityIcon } from '@/components/entity-icon';

export function CategoryLabel({
    name,
    icon,
}: {
    name: string | null;
    icon: string | null;
}) {
    if (name === null) {
        return '—';
    }

    return (
        <span className="inline-flex items-center gap-2">
            <EntityIcon name={icon} />
            <span>{name}</span>
        </span>
    );
}
