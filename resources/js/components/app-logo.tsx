import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            {/* La même identité que partout ailleurs sur le site : la pastille
                or du logo, l'encre marine dessus. Le back-office est le même
                produit que la vitrine, et c'est son logo qui le dit en premier. */}
            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-accent-brand">
                <AppLogoIcon className="size-4 text-accent-brand-foreground" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate font-display leading-tight font-extrabold tracking-tight">
                    {name}
                </span>
            </div>
        </>
    );
}
