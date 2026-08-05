import { Reveal } from '@/components/reveal';
import { useSlideshow } from '@/hooks/use-slideshow';
import { useTranslations } from '@/hooks/use-translations';
import {
    CONVERSATION_SLOT,
    PHOTO_SLOTS,
    conversationShots,
    photoFor,
} from '@/lib/photos';
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
function Brief({
    slot,
    label,
    className,
}: {
    slot: PhotoSlot;
    label: string;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col justify-end rounded-3xl border-2 border-dashed border-primary/40 bg-primary/[0.04] p-6',
                className,
            )}
        >
            <p className="font-display text-eyebrow font-extrabold text-primary uppercase">
                {label} · à fournir
            </p>
            <p className="mt-3 font-mono text-xs break-all text-muted-foreground">
                resources/js/images/photos/{slot.name}.webp
            </p>
            <p className="mt-4 text-sm text-muted-foreground">{slot.brief}</p>
        </div>
    );
}

/**
 * La conversation, dans un téléphone, à côté des trois étapes.
 *
 * Le cadre est allusif et non une reproduction d'appareil : deux ou trois
 * pixels de marge autour de la capture et un grand rayon suffisent à dire
 * « téléphone ». Un vrai châssis, avec son encoche et son reflet, aurait daté la
 * page le jour où le modèle dessiné aura cinq ans.
 *
 * Il vit sur la bande bleue, où les jetons sont redéclarés : `card` y est le
 * blanc et `border` un bleu clair. Le cadre suit donc la bande sans que rien ici
 * ne sache qu'il est posé sur du bleu.
 */
/** Le temps qu'un écran reste avant que le suivant ne prenne sa place. */
const SHOT_DURATION_MS = 4000;

export function ConversationShot({ className }: { className?: string }) {
    const t = useTranslations();

    const shots = conversationShots();
    const { current, setCurrent, holdHandlers } = useSlideshow(
        shots.length,
        SHOT_DURATION_MS,
    );

    if (shots.length === 0 && !import.meta.env.DEV) {
        return null;
    }

    return (
        <Reveal
            from="scale"
            delay={120}
            className={cn('mx-auto w-full max-w-[17rem]', className)}
        >
            {shots.length > 0 ? (
                <div {...holdHandlers}>
                    <div className="rounded-[2.25rem] bg-card p-2 shadow-2xl shadow-black/25">
                        {/* La proportion est celle des captures elles-mêmes.
                            Elle est imposée ici, alors qu'un écran unique
                            prenait la hauteur de son fichier : plusieurs écrans
                            qui se remplacent doivent occuper exactement la même
                            place, sinon la page se soulève à chaque passage. */}
                        <div className="relative aspect-[1003/2048] overflow-hidden rounded-[1.75rem]">
                            {shots.map((shot, index) => (
                                <img
                                    key={shot.src}
                                    src={shot.src}
                                    alt={t(shot.alt)}
                                    // Le premier écran est celui qu'on voit
                                    // arriver ; les suivants attendent leur
                                    // tour et peuvent se charger plus tard.
                                    loading={index === 0 ? 'eager' : 'lazy'}
                                    decoding="async"
                                    aria-hidden={index !== current}
                                    className={cn(
                                        'absolute inset-0 size-full object-cover transition-opacity duration-700 ease-out motion-reduce:transition-none',
                                        index === current
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )}
                                />
                            ))}
                        </div>
                    </div>

                    {shots.length > 1 && (
                        <div className="mt-5 flex items-center justify-center gap-2">
                            {shots.map((shot, index) => (
                                <button
                                    key={shot.src}
                                    type="button"
                                    onClick={() => setCurrent(index)}
                                    aria-label={`Écran ${index + 1} sur ${shots.length}`}
                                    aria-current={index === current}
                                    className={cn(
                                        'h-1.5 rounded-full transition-all duration-300 outline-none focus-visible:ring-2 focus-visible:ring-ring/60',
                                        index === current
                                            ? 'w-6 bg-foreground'
                                            : 'w-1.5 bg-foreground/30 hover:bg-foreground/50',
                                    )}
                                />
                            ))}
                        </div>
                    )}
                </div>
            ) : (
                <Brief
                    slot={CONVERSATION_SLOT}
                    label="Captures du chat"
                    className="aspect-[9/19] rounded-[2.25rem]"
                />
            )}
        </Reveal>
    );
}

export function ProcessPhotos({ className }: { className?: string }) {
    const t = useTranslations();

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
                'grid gap-x-8 gap-y-16 sm:grid-cols-2 sm:gap-x-12 lg:grid-cols-3 lg:gap-x-20 lg:gap-y-24',
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
                        <Brief
                            slot={slot}
                            label={`Emplacement ${index + 1}`}
                            className="aspect-[4/5]"
                        />
                    )}

                    <figcaption className="mt-7">
                        <p className="font-display text-lg font-extrabold">
                            {t(slot.title)}
                        </p>
                        <p className="mt-3 text-body text-muted-foreground">
                            {t(slot.caption)}
                        </p>
                    </figcaption>
                </Reveal>
            ))}
        </div>
    );
}
