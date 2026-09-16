import { cn } from '@/lib/utils';
import { forwardRef } from 'react';
import type { InputHTMLAttributes } from 'react';

const TextInput = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
    ({ className, ...props }, ref) => (
        <input
            ref={ref}
            className={cn(
                'w-full rounded-md border border-tavern-700 bg-tavern-950 px-3 py-2 text-parchment-100 placeholder:text-parchment-300/40 focus:border-parchment-300 focus:outline-none',
                className,
            )}
            {...props}
        />
    ),
);
TextInput.displayName = 'TextInput';

export default TextInput;
