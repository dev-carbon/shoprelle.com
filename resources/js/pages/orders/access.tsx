import { Form, Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

            <Card className="mx-auto max-w-md animate-rise">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <KeyRound className="size-4" />
                        Mes demandes
                    </CardTitle>
                    <CardDescription>
                        Retrouvez vos demandes et vos devis avec le numéro
                        utilisé pour les passer et votre code d'accès.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <Form {...store.form()} className="space-y-4">
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
            <p className="mx-auto mt-6 max-w-md text-center text-sm text-muted-foreground">
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
