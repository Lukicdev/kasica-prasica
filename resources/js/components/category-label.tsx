import { EntityIcon } from '@/components/entity-icon';

export function CategoryLabel({
    name,
    icon,
    color,
}: {
    name: string | null;
    icon: string | null;
    color?: string | null;
}) {
    if (name === null) {
        return '—';
    }

    return (
        <span className="inline-flex items-center gap-2">
            <EntityIcon name={icon} color={color} />
            <span>{name}</span>
        </span>
    );
}
