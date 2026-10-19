import type { Auth } from '@/types/auth';
import type { Currencies } from '@/types/currency';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            currencies: Currencies;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
