import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className,
}: {
    className?: string;
}) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: Monitor, label: 'System' },
    ];

    return (
        <ToggleGroup
            type="single"
            variant="outline"
            value={appearance}
            aria-label="Appearance"
            className={cn(className)}
            onValueChange={(next) => {
                if (next === 'light' || next === 'dark' || next === 'system') {
                    updateAppearance(next);
                }
            }}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <ToggleGroupItem key={value} value={value}>
                    <Icon data-icon="inline-start" />
                    {label}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
