import { BadgeCheck, Check } from 'lucide-react';
import type { ComponentProps } from 'react';

import { useSlideshow } from '@/hooks/use-slideshow';
import delivered from '@/images/colis-livre.webp';
import ordering from '@/images/commande-telephone.webp';
import family from '@/images/famille-colis.webp';
import jacket from '@/images/products/veste-matelassee.webp';
import { flagFor } from '@/lib/destinations';
import { cn } from '@/lib/utils';

/** Le temps qu'une photo reste avant que l'autre ne prenne sa place. */
const PHOTO_DURATION_MS = 5000;

/**
 * Le service, dans l'ordre où on veut le croire.
 *
 * Le colis reçu d'abord, parce que c'est la preuve et que c'est elle qui doit
 * être peinte en premier. La famille qui l'ouvre ensuite : c'est ce que la
 * livraison vaut une fois faite, et c'est la seule image de la page où le
 * service a déjà servi à quelque chose. La commande passée en dernier, qui
 * montre le geste qu'on demande au visiteur — commencer par elle aurait ouvert
 * la page sur une promesse.
 */
const PHOTOS = [
    {
        src: delivered,
        alt: 'Une cliente reçoit son colis Shoprelle devant chez elle, à Douala.',
    },
    {
        src: family,
        alt: 'Une famille ouvre son carton Shoprelle dans son salon, entourée des colis Shein, Amazon et AliExpress qu’il regroupait.',
    },
    {
        src: ordering,
        alt: 'Une cliente passe sa demande depuis son téléphone, chez elle, à côté de ses colis Shoprelle.',
    },
] as const;

/**
 * ── La preuve, en haut de page ──────────────────────────────────────────────
 *
 * La photo occupait le milieu du site, à côté d'un titre, là où plus personne
 * n'a besoin d'être convaincu. Elle remonte ici : quelqu'un qui reçoit son
 * colis devant chez lui, à côté de la promesse et du champ qui la déclenche.
 * C'est la seule chose sur cette page qui montre le service tenu plutôt que
 * décrit, et c'est pour ça qu'elle est au-dessus de la ligne de flottaison.
 *
 * Deux vignettes se posent dessus, et elles ne disent pas la même sorte de
 * chose :
 *
 *   — celle du haut est une illustration, comme les cartes de la frise. Elle
 *     montre à quoi ressemble un devis, avec un produit et un montant qui sont
 *     des exemples. Elle est donc `aria-hidden`, et rien d'informatif n'en
 *     dépend.
 *   — celle du bas est un fait : le nombre de pays réellement desservis, compté
 *     sur la même configuration que l'assistant applique. Elle est lue.
 *
 * Cette distinction est la raison d'être du composant. Une vignette flottante
 * est un procédé de mise en scène ; l'employer pour faire passer un chiffre
 * inventé pour une mesure serait exactement le genre de crédibilité que ce site
 * n'a pas le droit de fabriquer.
 */
export function HeroShowcase({
    countries,
    destinationCodes,
    className,
    ...props
}: {
    /** Le nombre de pays desservis, tel que le contrôleur l'a compté. */
    countries: number;
    /** Deux ou trois codes alpha-2, pour les drapeaux de la vignette basse. */
    destinationCodes: string[];
} & Omit<ComponentProps<'div'>, 'children'>) {
    const { current, setCurrent, holdHandlers } = useSlideshow(
        PHOTOS.length,
        PHOTO_DURATION_MS,
    );

    return (
        // Le halo doré qui débordait du cadre est parti : la photo a désormais
        // deux vignettes posées dessus et une paire de puces, et un rond de
        // lumière derrière tout ça faisait un fond de plus à démêler. L'ombre
        // portée suffit à décoller l'image de la page.
        //
        // `isolate` reste : les vignettes sont positionnées par rapport à ce
        // conteneur, et un contexte d'empilement déclaré ici les garde au-dessus
        // du fond de la section — la carte du monde en pointillés — pendant
        // l'animation d'entrée comme après.
        <div {...props} className={cn('relative isolate', className)}>
            <figure className="relative" {...holdHandlers}>
                {/* Verticale sur grand écran, panoramique en dessous : dans une
                    colonne de hero une image large s'écrase, et sur un
                    téléphone une image haute pousse le champ hors de l'écran.
                    Le cadrage centré garde le visage et le carton dans les deux
                    proportions.

                    L'ombre est épinglée en noir plutôt que prise sur l'encre de
                    la page : en thème sombre, celle-ci est la crème, et la photo
                    se serait retrouvée entourée d'un halo clair. */}
                <div className="relative aspect-[16/10] w-full overflow-hidden rounded-3xl shadow-2xl shadow-black/25 lg:aspect-[4/5]">
                    {PHOTOS.map((photo, index) => (
                        <img
                            key={photo.src}
                            src={photo.src}
                            alt={photo.alt}
                            width={1200}
                            height={655}
                            // La première photo est ce que le navigateur peint
                            // en haut de page : elle garde sa priorité. La
                            // seconde passe après, mais se charge quand même
                            // d'emblée — différée, elle apparaissait par
                            // à-coups au moment de prendre la place.
                            fetchPriority={index === 0 ? 'high' : 'low'}
                            loading="eager"
                            decoding="async"
                            aria-hidden={index !== current}
                            className={cn(
                                'absolute inset-0 size-full object-cover transition-opacity duration-700 ease-out motion-reduce:transition-none',
                                index === current ? 'opacity-100' : 'opacity-0',
                            )}
                        />
                    ))}
                </div>

                {/* Un liseré intérieur plutôt qu'une bordure : sur une photo,
                    un trait posé à l'extérieur du cadre se voit comme un cadre,
                    et un trait blanc à 10 % posé à l'intérieur ne fait que
                    détacher l'image du fond. */}
                <span
                    aria-hidden
                    className="pointer-events-none absolute inset-0 rounded-3xl ring-1 ring-white/15 ring-inset"
                />

                {/* Les puces, dans l'angle resté libre — les deux vignettes
                    occupent l'autre diagonale. Elles ne sont pas décoratives :
                    une image qui change toute seule doit pouvoir être arrêtée
                    et reprise par qui n'utilise pas la souris. */}
                <div className="absolute bottom-7 left-4 flex items-center gap-2 sm:left-5">
                    {PHOTOS.map((photo, index) => (
                        <button
                            key={photo.src}
                            type="button"
                            onClick={() => setCurrent(index)}
                            aria-label={`Photo ${index + 1} sur ${PHOTOS.length}`}
                            aria-current={index === current}
                            className={cn(
                                'h-1.5 rounded-full shadow-sm shadow-black/30 transition-all duration-300 outline-none focus-visible:ring-2 focus-visible:ring-white/80',
                                index === current
                                    ? 'w-6 bg-white'
                                    : 'w-1.5 bg-white/50 hover:bg-white/80',
                            )}
                        />
                    ))}
                </div>

                {/* Les deux vignettes.

                    Elles chevauchent les bords de la photo à partir de `sm`,
                    où le cadre a de la marge à gauche et à droite : entièrement
                    dedans elles se liraient comme des incrustations,
                    entièrement dehors comme deux cartes de plus.

                    Sur un téléphone cette marge n'existe pas, et flotter ne
                    voulait plus dire que couvrir : les deux vignettes mangeaient
                    le tiers d'une photo qui est la seule preuve de la page.
                    Elles passent donc en dessous, posées côte à côte. Le
                    conteneur disparaît en `display: contents` dès `sm` — les
                    vignettes redeviennent alors des enfants directs du
                    `<figure>`, qui est ce par rapport à quoi elles se
                    positionnent. */}
                <div className="mt-5 flex flex-wrap gap-3 sm:contents">
                    {/* Illustrative, et cachée aux lecteurs d'écran pour cette
                        raison : le produit et le montant sont des exemples. */}
                    <div
                        aria-hidden
                        className="flex items-center gap-3 rounded-2xl border panel-tile p-3 pr-5 shadow-xl shadow-black/15 sm:absolute sm:top-6 sm:-left-6 sm:animate-float"
                    >
                        <img
                            src={jacket}
                            alt=""
                            width={48}
                            height={48}
                            className="size-12 shrink-0 rounded-xl object-cover"
                        />

                        <div>
                            <p className="flex items-center gap-1.5 text-xs font-semibold text-success">
                                <Check className="size-3.5" />
                                Devis validé
                            </p>
                            <p className="mt-1 font-display text-sm font-extrabold tabular-nums">
                                24 500 XAF
                            </p>
                        </div>
                    </div>

                    {/* Un fait, donc lue. */}
                    <div className="flex items-center gap-3 rounded-2xl border panel-tile p-3 pr-5 shadow-xl shadow-black/15 sm:absolute sm:-right-6 sm:bottom-6 sm:animate-float-slow">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                            <BadgeCheck className="size-5" />
                        </span>

                        <div>
                            <p className="font-display text-sm font-extrabold tabular-nums">
                                {countries} pays desservis
                            </p>
                            <p
                                aria-hidden
                                className="mt-1 text-lg leading-none tracking-widest"
                            >
                                {destinationCodes.map((code) => (
                                    <span key={code}>{flagFor(code)}</span>
                                ))}
                            </p>
                        </div>
                    </div>
                </div>
            </figure>
        </div>
    );
}
