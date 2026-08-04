import momoMtn from '@/images/payments/momo-mtn.webp';
import orangeMoney from '@/images/payments/orange-money.svg';
import paypal from '@/images/payments/paypal.svg';

/**
 * ── Les marques des moyens de paiement ──────────────────────────────────────
 *
 * Elles ne suivent pas la règle des logos de plateformes, et c'est délibéré.
 * Là-bas, l'artwork est monochrome et prend la couleur de sa tuile ; ici les
 * trois marques sont bicolores — le bleu et le jaune de MoMo, l'orange et le
 * noir d'Orange money, les deux bleus de PayPal — et les repeindre d'une seule
 * couleur reviendrait à les redessiner. Elles sont donc posées telles quelles,
 * en `<img>`, avec leurs propres couleurs.
 *
 * Ce qui impose leur fond : une tuile blanche, dans les deux thèmes. Le bleu
 * marine de PayPal et celui de MoMo disparaîtraient sur le fond sombre de la
 * page en thème nuit. C'est la même tuile que celle des plateformes, pour la
 * même raison.
 *
 * La clé est le nom exact du moyen de paiement tel que la configuration le
 * donne — voir `payment.methods` dans `config/shoprelle.php`. Un moyen absent
 * de cette table n'est pas une erreur : il retombe sur sa pastille de couleur
 * et son nom écrit, ce qui était l'affichage de tous avant que ces trois-là
 * n'aient leur marque.
 */
export type PaymentLogo = {
    /** L'URL de l'artwork, passée par le pipeline de compilation. */
    readonly src: string;
    /** La largeur divisée par la hauteur, lue sur le fichier source. */
    readonly ratio: number;
};

export const PAYMENT_LOGOS: Record<string, PaymentLogo> = {
    'MTN Mobile Money': { src: momoMtn, ratio: 628 / 294 },
    'Orange Money': { src: orangeMoney, ratio: 431 / 115 },
    PayPal: { src: paypal, ratio: 124 / 33 },
};
