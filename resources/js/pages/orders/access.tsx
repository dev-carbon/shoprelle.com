import { Form, Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';

import InputError from '@/components/input-error';
import { Accent, Eyebrow } from '@/components/section-heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { CustomerLayout } from '@/layouts/customer-layout';
import { show as showChat } from '@/routes/chat';
import { store } from '@/routes/orders/access';

export default function OrdersAccess() {
    return (
        <CustomerLayout>
            <Head title="Mes demandes" />

            {/* L'en-tête de page à part entière, sur l'échelle du site : le
                sur-titre flottait seul au-dessus de la carte, et le titre de la
                page vivait dans la carte comme un intitulé de formulaire. Une
                page se présente d'abord, son formulaire vient ensuite. */}
            <header className="mx-auto max-w-md animate-rise text-center">
                <Eyebrow tone="gold">Votre espace</Eyebrow>

                <h1 className="mt-6 font-display text-title font-black">
                    Mes <Accent tone="blue">demandes</Accent>
                </h1>
                <p className="mt-4 text-body text-muted-foreground">
                    Retrouvez vos demandes et vos devis avec le numéro utilisé
                    pour les passer et votre code d'accès.
                </p>
            </header>

            <Card className="mx-auto mt-10 max-w-md animate-rise">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-sm">
                        <KeyRound className="size-4 text-muted-foreground" />
                        Identifiez-vous
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <Form {...store.form()} className="space-y-6">
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-1.5">
                                    <Label htmlFor="phone">
                                        Numéro de téléphone
                                    </Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        autoComplete="tel"
                                        placeholder="+237 6 XX XX XX XX"
                                        required
                                        autoFocus
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="code">Code d'accès</Label>
                                    <Input
                                        id="code"
                                        name="code"
                                        placeholder="K4M-9PZ"
                                        autoComplete="off"
                                        autoCapitalize="characters"
                                        className="font-mono tracking-widest"
                                        required
                                    />
                                    <InputError message={errors.code} />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {processing && <Spinner />}
                                    Voir mes demandes
                                </Button>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>

            {/* The code cannot be resent — it is stored hashed and nobody can
                read it back — so the only honest advice is to ask us. */}
            <p className="mx-auto mt-10 max-w-md text-center text-sm text-muted-foreground">
                Votre code vous a été donné à la fin de votre première demande.
                Vous l'avez perdu ? Écrivez-nous depuis{' '}
                <a
                    href={showChat().url}
                    className="text-primary underline underline-offset-4"
                >
                    l'assistant
                </a>{' '}
                : nous vous en donnerons un nouveau.
            </p>
        </CustomerLayout>
    );
}
