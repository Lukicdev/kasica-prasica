import { EntityIcon } from '@/components/entity-icon';

export function AccountLabel({
    name,
    icon,
    destination,
    destinationIcon,
}: {
    name: string;
    icon: string | null;
    destination?: string | null;
    destinationIcon?: string | null;
}) {
    return (
        <span className="inline-flex flex-wrap items-center gap-x-2 gap-y-1">
            <span className="inline-flex items-center gap-2">
                <EntityIcon name={icon} />
                <span>{name}</span>
            </span>
            {destination ? (
                <>
                    <span className="sr-only">to</span>
                    <span aria-hidden="true">→</span>
                    <span className="inline-flex items-center gap-2">
                        <EntityIcon name={destinationIcon ?? null} />
                        <span>{destination}</span>
                    </span>
                </>
            ) : null}
        </span>
    );
}
