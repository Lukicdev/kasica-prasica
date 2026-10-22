import { Label } from '@/components/ui/label';
import { Toggle } from '@/components/ui/toggle';
import { categoryColorLabel, categoryColors } from '@/lib/category-colors';
import { cn } from '@/lib/utils';

export function ColorPicker({
    id,
    name = 'color',
    value,
    onChange,
}: {
    id: string;
    name?: string;
    value: string | null;
    onChange: (value: string | null) => void;
}) {
    const selected = categoryColorLabel(value);

    return (
        <div className="grid gap-2">
            <Label id={`${id}-label`}>Color</Label>
            <input type="hidden" name={name} value={value ?? ''} />
            <div
                id={id}
                role="group"
                aria-labelledby={`${id}-label`}
                className="flex flex-wrap gap-2"
                data-test="color-picker"
            >
                <Toggle
                    type="button"
                    variant="outline"
                    pressed={value === null}
                    aria-label="No color"
                    title="No color"
                    data-test="color-none"
                    className="size-11 p-0 data-[state=on]:bg-transparent data-[state=on]:ring-2 data-[state=on]:ring-ring"
                    onPressedChange={() => onChange(null)}
                >
                    <span className="size-5 rounded-sm bg-muted" />
                </Toggle>
                {categoryColors.map((option) => (
                    <Toggle
                        key={option.value}
                        type="button"
                        variant="outline"
                        pressed={value === option.value}
                        aria-label={option.label}
                        title={option.label}
                        data-test="color-option"
                        className="size-11 p-0 data-[state=on]:bg-transparent data-[state=on]:ring-2 data-[state=on]:ring-ring"
                        onPressedChange={(pressed) => {
                            if (pressed) {
                                onChange(option.value);
                            }
                        }}
                    >
                        <span
                            className={cn(
                                'size-5 rounded-sm',
                                option.className,
                            )}
                        />
                    </Toggle>
                ))}
            </div>
            <p className="text-sm text-muted-foreground">
                {selected
                    ? `${selected} behind the icon.`
                    : 'Shown behind the icon.'}
            </p>
        </div>
    );
}
