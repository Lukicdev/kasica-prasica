'use client';

import * as React from 'react';

import { cn } from '@/lib/utils';

export type ChartConfig = Record<
    string,
    {
        label?: string;
        color?: string;
    }
>;

const ChartContext = React.createContext<{
    config: ChartConfig;
    configKey: string;
} | null>(null);

function useChart() {
    const context = React.useContext(ChartContext);
    if (!context) {
        throw new Error('useChart must be used within a ChartContainer');
    }
    return context;
}

interface ChartContainerProps extends React.ComponentProps<'div'> {
    config: ChartConfig;
    configKey?: string;
    children: React.ReactNode;
}

const ChartContainer = React.forwardRef<HTMLDivElement, ChartContainerProps>(
    ({ id, className, config, configKey = 'value', children, ...props }, ref) => {
        const uniqueId = React.useId();
        const chartId = id ?? `chart-${uniqueId.replace(/:/g, '')}`;

        const style = React.useMemo(() => {
            return Object.entries(config).reduce(
                (acc, [key, { color }]) => {
                    if (color) {
                        acc[`--color-${key}` as string] = color;
                    }
                    return acc;
                },
                {} as Record<string, string>
            );
        }, [config]);

        return (
            <ChartContext.Provider value={{ config, configKey }}>
                <div
                    ref={ref}
                    id={chartId}
                    className={cn("w-full", className)}
                    style={style as React.CSSProperties}
                    {...props}
                >
                    {children}
                </div>
            </ChartContext.Provider>
        );
    }
);
ChartContainer.displayName = 'ChartContainer';

interface ChartTooltipContentProps extends React.ComponentProps<'div'> {
    hideLabel?: boolean;
    formatter?: (value: number) => string;
    nameKey?: string;
    valueKey?: string;
    /** Props injected by Recharts Tooltip */
    active?: boolean;
    payload?: Array<{
        name?: string;
        value?: number;
        dataKey?: string;
        payload?: Record<string, unknown>;
    }>;
    label?: React.ReactNode;
}

function ChartTooltipContent({
    hideLabel,
    formatter = (value) => value.toLocaleString(),
    nameKey = 'name',
    valueKey = 'value',
    active,
    payload,
    label,
    className,
    ...props
}: ChartTooltipContentProps) {
    if (!active || !payload?.length) {
        return null;
    }
    const item = payload[0];
    const rawPayload = item.payload ?? {};
    const name =
        (label as string) ??
        (rawPayload[nameKey] as string) ??
        (item.name as string) ??
        '';
    const value = (rawPayload[valueKey] as number) ?? (item.value as number) ?? 0;
    return (
        <div
            className={cn(
                "rounded-lg border border-border bg-background px-3 py-2 text-sm shadow-md",
                className
            )}
            {...props}
        >
            {!hideLabel && name ? (
                <div className="font-medium text-foreground">{name}</div>
            ) : null}
            <div className="text-muted-foreground">{formatter(Number(value))}</div>
        </div>
    );
}

export { ChartContainer, ChartTooltipContent, useChart };
