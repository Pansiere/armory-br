import { cn } from '@/lib/utils';
import type { LabelHTMLAttributes } from 'react';

export default function InputLabel({
    className,
    children,
    ...props
}: LabelHTMLAttributes<HTMLLabelElement>) {
    return (
        <label
            className={cn('block text-sm font-medium text-parchment-200', className)}
            {...props}
        >
            {children}
        </label>
    );
}
