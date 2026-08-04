import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    /**
     * Quel cadre entoure quelle page.
     *
     * Les pages du back-office sont nommées une à une, et tout le reste n'a
     * aucun cadre. C'est l'inverse d'avant, où le cas par défaut était
     * `AppLayout` : une page publique ajoutée sans penser à ce fichier
     * héritait alors de la barre latérale de l'administration, et « Mes
     * demandes » l'a effectivement montrée à ses clients.
     *
     * Les deux erreurs possibles n'ont pas le même prix. Oublier une page
     * d'administration ici lui retire son cadre, ce qui se voit à la seconde
     * où on l'ouvre ; oublier une page publique lui donnait le cadre des
     * autres, ce qui ne se voit que si quelqu'un regarde. Le défaut va donc du
     * côté qui échoue bruyamment.
     */
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name === 'dashboard':
            case name.startsWith('admin/'):
                return AppLayout;
            // La vitrine, l'assistant, l'espace client : le visiteur ne voit
            // jamais le back-office, et ces pages portent leur propre cadre.
            default:
                return null;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
