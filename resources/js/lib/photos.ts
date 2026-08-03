/**
 * ── Les photographies du site ───────────────────────────────────────────────
 *
 * Le site n'avait qu'une photo, et elle tient le hero. Il en manque trois, et
 * plutôt que de les attendre pour construire la section qui les reçoit, celle-ci
 * lit ce qui est sur le disque : déposer `achat-france.webp` dans
 * `resources/js/images/photos/` suffit à ce que la photo apparaisse. Rien à
 * importer, rien à déclarer, aucune ligne de code à toucher.
 *
 * Le corollaire est ce qui rend la chose sûre : un emplacement dont le fichier
 * n'existe pas disparaît en production, et la section entière disparaît si
 * aucun des trois n'est là. Un site de confiance ne montre pas un cadre gris
 * portant « photo à venir » ; il montre ce qu'il a. En développement, le cadre
 * est au contraire affiché avec sa consigne, pour qu'on n'oublie pas ce qui
 * reste à faire — voir `PhotoSlot` dans le composant qui les rend.
 *
 * ── Ce que ces trois photos doivent avoir en commun
 *
 * Elles complètent celle du hero et doivent en être la suite, pas le voisinage.
 * Concrètement :
 *
 *   — de vraies personnes, de vrais lieux. Pas de studio, pas de fond blanc,
 *     pas de sourire de banque d'images. La photo du hero marche parce qu'on
 *     croit à la rue derrière la cliente ; trois photos de stock à côté
 *     d'elle la feraient passer pour du stock elle aussi.
 *   — la lumière du jour, et le même registre chaud.
 *   — des mains, des gestes, des objets. Le service est une suite d'actions
 *     faites par quelqu'un : c'est ça qu'il faut voir, pas des visages qui
 *     posent.
 *   — le carton Shoprelle quand il a lieu d'être. C'est le seul élément de
 *     marque qui relie les quatre images entre elles.
 *   — format paysage, au moins 1600 px de large, en `.webp`. Le cadrage
 *     s'affiche en 4/5 : ce qui compte doit tenir au centre.
 */

/**
 * Les fichiers présents, relevés à la compilation.
 *
 * `import.meta.glob` est résolu par Vite au moment du build : ce qui arrive
 * ici est la liste des fichiers réellement sur le disque, avec leur URL une
 * fois passés par le pipeline (hachée, optimisée, mise en cache). Un dossier
 * vide donne un objet vide, jamais une erreur — c'est ce qui permet à ce module
 * d'exister avant les photos qu'il décrit.
 */
const FILES = import.meta.glob<string>('../images/photos/*.webp', {
    eager: true,
    import: 'default',
});

/**
 * Les trois emplacements, dans l'ordre du parcours.
 *
 * Le nom du cas est le nom du fichier attendu, sans extension. `brief` est ce
 * que la photo doit montrer — il n'est affiché qu'en développement, dans le
 * cadre vide, mais il est ici plutôt que dans un document à côté pour la même
 * raison que tout le reste de ce fichier : c'est le code qui sait quels
 * emplacements existent, et une consigne rangée ailleurs se périme.
 */
export type PhotoSlot = {
    /** Le nom du fichier attendu dans `resources/js/images/photos/`. */
    readonly name: string;
    /** Le titre affiché sous la photo. */
    readonly title: string;
    /** La légende : une phrase, et rien que ce que la page tient déjà. */
    readonly caption: string;
    /** Ce que la photo doit montrer. Développement seulement. */
    readonly brief: string;
};

export const PHOTO_SLOTS: readonly PhotoSlot[] = [
    {
        name: 'achat-france',
        title: 'On achète pour vous',
        caption:
            "La commande est passée depuis la France, avec nos moyens de paiement. Vous n'avez pas de carte à faire accepter à l'étranger.",
        brief: "L'achat en train de se faire : un écran de plateforme, une carte bancaire, des mains. Le geste, pas le visage. Prouve qu'il y a quelqu'un de réel derrière le mot « nous achetons ».",
    },
    {
        name: 'preparation-colis',
        title: 'On regroupe vos achats',
        caption:
            'Plusieurs produits, plusieurs sites, un seul carton et un seul transport. C’est là que la facture se joue.',
        brief: 'Des colis marchands rassemblés dans un carton Shoprelle, étiquette de référence visible, ruban en cours de pose. Prouve le regroupement — donc un seul transport payé au lieu de quatre.',
    },
    {
        name: 'remise-colis',
        title: 'On vous le remet',
        caption:
            'Jusqu’à votre ville, et jusqu’à vous. Votre référence vous dit où il en est tout du long.',
        brief: 'La remise du colis, sur place. Cadrage volontairement différent de la photo du hero — plan plus large, ou deux personnes, ou de dos — pour que les deux ne se répètent pas.',
    },
] as const;

/**
 * L'URL de la photo d'un emplacement, ou `null` si le fichier n'existe pas.
 */
export const photoFor = (slot: PhotoSlot): string | null =>
    FILES[`../images/photos/${slot.name}.webp`] ?? null;

/**
 * Si la bande photo a lieu d'être.
 *
 * En production : dès qu'au moins une des trois photos existe. En
 * développement : toujours, pour que les cadres vides et leurs consignes soient
 * sous les yeux de qui travaille sur la page.
 *
 * Le balisage de la section reste dans le paquet même quand elle ne s'affiche
 * pas : ce test est un appel, pas une constante que le compactage saurait
 * réduire. C'est quelques centaines d'octets de JSX qui ne rendent rien, et
 * c'est le prix à payer pour que déposer un fichier suffise — l'alternative
 * était d'importer les trois photos nommément, donc de ne pas pouvoir écrire la
 * section avant de les avoir.
 */
export const SHOW_PROCESS_PHOTOS: boolean =
    import.meta.env.DEV || PHOTO_SLOTS.some((slot) => photoFor(slot) !== null);
