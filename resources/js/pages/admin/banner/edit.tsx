import { Form, Head } from '@inertiajs/react';
import { Megaphone } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit as bannerEdit, update } from '@/routes/admin/banner';

type Props = {
    banner: { enabled: boolean; message: string; message_en: string };
};

/**
 * ── Le bandeau de promotion de la vitrine ───────────────────────────────────
 *
 * Un interrupteur et deux messages, rien de plus : ce que dit la première
 * ligne du site se change ici, sans déploiement. Le message anglais est servi
 * aux visiteurs qui ont basculé la vitrine en anglais ; laissé vide, tout le
 * monde lit le français.
 */
export default function BannerEdit({ banner }: Props) {
    return (
        <>
            <Head title="Bandeau" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div>
                    <h1 className="font-display text-2xl font-extrabold tracking-tight">
                        Bandeau
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        La ligne de promotion affichée tout en haut de la
                        vitrine. Décochez pour la retirer sans perdre le texte.
                    </p>
                </div>

                <div className="max-w-2xl rounded-xl border bg-card p-5">
                    <Form {...update.form()} options={{ preserveScroll: true }}>
                        {({ processing, errors }) => (
                            <div className="space-y-6">
                                <label className="flex items-center gap-2 text-sm font-medium">
                                    <input
                                        type="hidden"
                                        name="enabled"
                                        value="0"
                                    />
                                    <input
                                        type="checkbox"
                                        name="enabled"
                                        value="1"
                                        defaultChecked={banner.enabled}
                                        className="size-4 rounded border-input"
                                    />
                                    Afficher le bandeau sur la vitrine
                                </label>

                                <div className="space-y-1.5">
                                    <Label htmlFor="message">
                                        Message (français)
                                    </Label>
                                    <Input
                                        id="message"
                                        name="message"
                                        maxLength={160}
                                        defaultValue={banner.message}
                                        placeholder="-50 % sur la livraison de votre première commande"
                                    />
                                    <InputError message={errors.message} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="message_en">
                                        Message (anglais)
                                        <span className="ml-2 text-xs font-normal text-muted-foreground">
                                            laisser vide pour servir le français
                                            partout
                                        </span>
                                    </Label>
                                    <Input
                                        id="message_en"
                                        name="message_en"
                                        maxLength={160}
                                        defaultValue={banner.message_en}
                                        placeholder="50% off delivery on your first order"
                                    />
                                    <InputError message={errors.message_en} />
                                </div>

                                <div className="flex items-center gap-3">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Enregistrer
                                    </Button>
                                    <Megaphone className="size-4 text-muted-foreground" />
                                    <p className="text-xs text-muted-foreground">
                                        Visible immédiatement après
                                        enregistrement.
                                    </p>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

BannerEdit.layout = {
    breadcrumbs: [{ title: 'Bandeau', href: bannerEdit() }],
};
