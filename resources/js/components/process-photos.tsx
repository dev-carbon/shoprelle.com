import { Reveal } from '@/components/reveal';
import { PHOTO_SLOTS, photoFor } from '@/lib/photos';
import type { PhotoSlot } from '@/lib/photos';
import { cn } from '@/lib/utils';

/**
 * ── Le parcours, photographié ───────────────────────────────────────────────
 *
 * Cette bande se place juste après « Trois étapes, et c'est tout ». Les mêmes
 * trois étapes, mais montrées : la section d'à côté dit ce qui se passe, celle-ci
 * montre que ça se passe. C'est la différence entre une promesse et une preuve,
 * et c'est tout ce que cette bande a à faire.
 *
 * Elle n'existe que si les photos existent. Voir `lib/photos.ts` : les fichiers
 * sont relevés sur le disque à la compilation, et un emplacement vide disparaît
 * en production plutôt que d'afficher un cadre. Un site dont l'argument est la
 * confiance ne montre pas de trous en attendant mieux.
 *
 * En développement, l'inverse : le cadre s'affiche avec la consigne de la photo
 * qui manque, parce qu'un emplacement invisible est un emplacement oublié.
 */

/** Le cadre vide, avec sa consigne. Développement seulement. */
function Brief({ slot, index }: { slot: PhotoSlot; index: number }) {
    return (
        <div className="flex aspect-[4/5] flex-col justify-end rounded-3xl border-2 border-dashed border-primary/40 bg-primary/[0.04] p-6">
            <p className="font-display text-eyebrow font-extrabold text-primary uppercase">
                Emplacement {index + 1} · à fournir
            </p>
            <p className="mt-3 font-mono text-xs break-all text-muted-foreground">
                resources/js/images/photos/{slot.name}.webp
            </p>
            <p className="mt-4 text-sm text-muted-foreground">{slot.brief}</p>
        </div>
    );
}

export function ProcessPhotos({ className }: { className?: string }) {
    const photos = PHOTO_SLOTS.map((slot) => ({
        slot,
        src: photoFor(slot),
    })).filter(({ src }) => src !== null || import.meta.env.DEV);

    if (photos.length === 0) {
        return null;
    }

    return (
        <div
            className={cn(
                'grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3',
                className,
            )}
        >
            {photos.map(({ slot, src }, index) => (
                <Reveal
                    as="figure"
                    from="scale"
                    key={slot.name}
                    delay={index * 120}
                    className={cn(
                        // La colonne du milieu descend d'un cran sur grand
                        // écran. Trois photos parfaitement alignées se lisent
                        // comme un tableau de résultats ; décalées, comme une
                        // composition. Rien de plus que ça — un décalage sur
                        // chacune ferait un escalier.
                        index === 1 && 'lg:mt-16',
                    )}
                >
                    {src ? (
                        <div className="group relative overflow-hidden rounded-3xl">
                            <img
                                src={src}
                                alt=""
                                loading="lazy"
                                decoding="async"
                                className="aspect-[4/5] w-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                            />

                            {/* Le numéro de l'étape, posé sur la photo : c'est
                                lui qui rattache les trois images aux trois
                                étapes racontées juste au-dessus. Sans lui, la
                                bande n'est qu'une galerie. */}
                            <span
                                aria-hidden
                                className="absolute top-5 left-5 flex size-10 items-center justify-center rounded-full panel-tile font-display text-sm font-black tabular-nums"
                            >
                                {index + 1}
                            </span>

                            {/* Un liseré intérieur, comme sur la photo du hero :
                                sur une image, un trait posé dehors se voit
                                comme un cadre. */}
                            <span
                                aria-hidden
                                className="pointer-events-none absolute inset-0 rounded-3xl ring-1 ring-black/10 ring-inset"
                            />
                        </div>
                    ) : (
                        <Brief slot={slot} index={index} />
                    )}

                    <figcaption className="mt-7">
                        <p className="font-display text-lg font-extrabold">
                            {slot.title}
                        </p>
                        <p className="mt-3 text-body text-muted-foreground">
                            {slot.caption}
                        </p>
                    </figcaption>
                </Reveal>
            ))}
        </div>
    );
}
