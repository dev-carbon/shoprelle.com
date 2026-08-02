import type { SVGAttributes } from 'react';

/**
 * La marque Shoprelle : le S du logotype, réduit à son geste.
 *
 * Dessinée à partir du mot lui-même — le wordmark du pied de page est en
 * Archivo 900, et cette lettre en reprend la construction : deux cercles de
 * même rayon empilés, une graisse qui tient la comparaison sans que les
 * contreformes se bouchent. Vérifiée à 64, 32 et 16 pixels, cette dernière
 * étant celle de l'onglet.
 *
 * Tracée et non remplie, terminaisons rondes. C'est ce qui la relie au reste du
 * système : les routes de la carte, les points du monde derrière le hero et les
 * pastilles des destinations sont toutes tracées en `round`. La marque n'est pas
 * un objet rapporté, elle est du même trait.
 *
 * Une seule couleur, héritée par `currentColor` : posée sur le carré doré elle
 * prend le navy, et rien n'est à changer quand elle passe sur un autre fond.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M27 13A7 7 0 1 0 20 20A7 7 0 1 1 13 27"
                stroke="currentColor"
                strokeWidth={7.5}
                strokeLinecap="round"
            />
        </svg>
    );
}
