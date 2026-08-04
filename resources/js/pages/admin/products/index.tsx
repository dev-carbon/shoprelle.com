import { Form, Head } from '@inertiajs/react';
import { ExternalLink, ImageOff, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import {
    destroy,
    index as productsIndex,
    store,
    update,
} from '@/routes/admin/products';

type Option = { value: string; label: string };

type ProductRow = {
    id: number;
    name: string;
    image_url: string | null;
    marketplace: string;
    marketplace_label: string;
    category: string;
    category_label: string;
    product_url: string;
    price: string | null;
    currency: string | null;
    is_featured: boolean;
    position: number;
};

type Props = {
    products: ProductRow[];
    marketplaces: Option[];
    categories: Option[];
    currency: string;
};

/**
 * ── La sélection montrée sur la vitrine ─────────────────────────────────────
 *
 * La liste et le formulaire sur un seul écran. Il n'y a jamais qu'une poignée
 * de produits mis en avant, et une page par produit ferait cliquer trois fois
 * pour corriger un prix.
 *
 * Le formulaire sert à l'ajout comme à la modification : le produit en cours
 * d'édition est un état local, et c'est lui qui décide de l'action visée et des
 * valeurs par défaut. Remonté par sa clé à chaque changement de produit, sinon
 * les champs gardent ce qu'on venait d'y taper pour le précédent.
 */
export default function ProductsIndex({
    products,
    marketplaces,
    categories,
    currency,
}: Props) {
    const [editing, setEditing] = useState<ProductRow | null>(null);
    const [open, setOpen] = useState(false);

    const start = (product: ProductRow | null) => {
        setEditing(product);
        setOpen(true);
    };

    const close = () => {
        setEditing(null);
        setOpen(false);
    };

    return (
        <>
            <Head title="Produits" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold">Produits</h1>
                        <p className="text-sm text-muted-foreground">
                            La sélection montrée sur la page d'accueil. Les prix
                            y sont indicatifs — seul le devis engage.
                        </p>
                    </div>

                    <Button onClick={() => start(null)}>
                        <Plus className="size-4" />
                        Ajouter un produit
                    </Button>
                </div>

                {open && (
                    <div className="rounded-xl border bg-card p-5">
                        <div className="mb-5 flex items-center justify-between gap-4">
                            <h2 className="font-semibold">
                                {editing
                                    ? `Modifier « ${editing.name} »`
                                    : 'Nouveau produit'}
                            </h2>

                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={close}
                                aria-label="Fermer le formulaire"
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <Form
                            key={editing?.id ?? 'new'}
                            {...(editing
                                ? update.form(editing.id)
                                : store.form())}
                            options={{ preserveScroll: true }}
                            onSuccess={close}
                            className="grid gap-5 sm:grid-cols-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-1.5 sm:col-span-2">
                                        <Label htmlFor="name">Nom</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={editing?.name ?? ''}
                                            maxLength={120}
                                            required
                                            autoFocus
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="space-y-1.5 sm:col-span-2">
                                        <Label htmlFor="product_url">
                                            Lien du produit
                                        </Label>
                                        <Input
                                            id="product_url"
                                            name="product_url"
                                            type="url"
                                            inputMode="url"
                                            placeholder="https://…"
                                            defaultValue={
                                                editing?.product_url ?? ''
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.product_url}
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="marketplace">
                                            Plateforme
                                        </Label>
                                        <select
                                            id="marketplace"
                                            name="marketplace"
                                            defaultValue={
                                                editing?.marketplace ??
                                                marketplaces[0]?.value
                                            }
                                            className="h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            {marketplaces.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.marketplace}
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="category">
                                            Catégorie
                                        </Label>
                                        <select
                                            id="category"
                                            name="category"
                                            defaultValue={
                                                editing?.category ??
                                                categories[0]?.value
                                            }
                                            className="h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            {categories.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.category} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="price">
                                            Prix indicatif ({currency})
                                        </Label>
                                        <Input
                                            id="price"
                                            name="price"
                                            type="number"
                                            inputMode="numeric"
                                            min={0}
                                            step="1"
                                            defaultValue={editing?.price ?? ''}
                                        />
                                        <InputError message={errors.price} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="position">Ordre</Label>
                                        <Input
                                            id="position"
                                            name="position"
                                            type="number"
                                            min={0}
                                            step="1"
                                            defaultValue={
                                                editing?.position ?? 0
                                            }
                                        />
                                        <InputError message={errors.position} />
                                    </div>

                                    <div className="space-y-1.5 sm:col-span-2">
                                        <Label htmlFor="image">
                                            Photo
                                            {editing && (
                                                <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                    laisser vide pour garder
                                                    l'actuelle
                                                </span>
                                            )}
                                        </Label>
                                        <Input
                                            id="image"
                                            name="image"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            required={editing === null}
                                        />
                                        <InputError message={errors.image} />
                                    </div>

                                    <label className="flex items-center gap-2 text-sm sm:col-span-2">
                                        {/* La case cochée par défaut : on
                                            ajoute un produit pour le montrer.
                                            Le champ caché qui la précède est ce
                                            qui fait arriver un `false` quand
                                            elle est décochée — un interrupteur
                                            non coché n'est simplement pas
                                            envoyé. */}
                                        <input
                                            type="hidden"
                                            name="is_featured"
                                            value="0"
                                        />
                                        <input
                                            type="checkbox"
                                            name="is_featured"
                                            value="1"
                                            defaultChecked={
                                                editing?.is_featured ?? true
                                            }
                                            className="size-4 rounded border-input"
                                        />
                                        Afficher sur la page d'accueil
                                    </label>

                                    <div className="flex items-center gap-3 sm:col-span-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {editing
                                                ? 'Enregistrer'
                                                : 'Ajouter'}
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={close}
                                        >
                                            Annuler
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                )}

                {products.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <p className="text-sm text-muted-foreground">
                            Aucun produit pour le moment. La section
                            correspondante n'apparaît pas sur la page d'accueil
                            tant qu'il n'y en a pas.
                        </p>
                    </div>
                ) : (
                    <ul className="grid gap-3">
                        {products.map((product) => (
                            <li
                                key={product.id}
                                className={cn(
                                    'flex flex-wrap items-center gap-4 rounded-xl border bg-card p-3',
                                    !product.is_featured && 'opacity-60',
                                )}
                            >
                                {product.image_url ? (
                                    <img
                                        src={product.image_url}
                                        alt=""
                                        className="size-16 shrink-0 rounded-lg border object-cover"
                                    />
                                ) : (
                                    <span className="flex size-16 shrink-0 items-center justify-center rounded-lg border text-muted-foreground">
                                        <ImageOff className="size-5" />
                                    </span>
                                )}

                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">
                                        {product.name}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {product.category_label} ·{' '}
                                        {product.marketplace_label}
                                        {product.price &&
                                            ` · ${product.price} ${product.currency ?? ''}`}
                                        {!product.is_featured && ' · masqué'}
                                    </p>
                                    <a
                                        href={product.product_url}
                                        target="_blank"
                                        rel="noopener noreferrer nofollow"
                                        className="mt-1 inline-flex items-center gap-1.5 text-xs text-primary hover:underline"
                                    >
                                        <span className="max-w-xs truncate">
                                            {product.product_url}
                                        </span>
                                        <ExternalLink className="size-3 shrink-0" />
                                    </a>
                                </div>

                                <div className="flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => start(product)}
                                    >
                                        <Pencil className="size-4" />
                                        Modifier
                                    </Button>

                                    <Form
                                        {...destroy.form(product.id)}
                                        options={{ preserveScroll: true }}
                                        onBefore={() =>
                                            confirm(
                                                `Supprimer « ${product.name} » ?`,
                                            )
                                        }
                                    >
                                        <Button
                                            type="submit"
                                            variant="ghost"
                                            size="sm"
                                            aria-label={`Supprimer ${product.name}`}
                                        >
                                            <Trash2 className="size-4 text-destructive" />
                                        </Button>
                                    </Form>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [{ title: 'Produits', href: productsIndex() }],
};
