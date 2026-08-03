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
 * ── Le quatrième emplacement, qui n'est pas une photographie ────────────────
 *
 * La conversation elle-même, dans un téléphone, à côté des trois étapes. Il est
 * à part des trois autres parce que tout y diffère : il est vertical, il n'a pas
 * à respecter la règle de lumière et de cadrage ci-dessus — c'est un écran, pas
 * une scène — et il ne se pose pas dans la même bande.
 *
 * Il comble le seul endroit vide de la page : la colonne de gauche de « Trois
 * étapes, et c'est tout » ne porte qu'un titre et une phrase, en face d'une
 * frise qui tient toute la hauteur. Y mettre la conversation elle-même est ce
 * qui manquait — la section explique un échange sans jamais le montrer.
 *
 * ── Plusieurs écrans plutôt qu'un
 *
 * Une capture unique ne pouvait montrer qu'un moment de l'échange, et le
 * choisir revenait à choisir ce qu'on ne montrerait pas. Le téléphone fait donc
 * défiler la suite : `conversation-1.webp`, `-2`, `-3`… numérotés dans l'ordre
 * du parcours. Le relevé est le même que pour les photos — ajouter un écran est
 * un fichier de plus, en retirer un est un fichier de moins.
 */
export const CONVERSATION_SLOT: PhotoSlot = {
    name: 'conversation-1',
    title: 'La conversation',
    caption: "Ce que voit un client, du premier lien jusqu'à sa référence.",
    brief: "Captures d'écran du chat, au format téléphone (portrait), nommées conversation-1.webp, conversation-2.webp… dans l'ordre du parcours. Une conversation réelle, du lien collé jusqu'à la référence — et rien qui ressemble à un vrai nom ou à un vrai numéro : ces images sont publiques.",
};

/**
 * Ce que chaque écran montre, pour qui ne voit pas l'image.
 *
 * Indexé sur le numéro du fichier. Un écran sans description reste lisible —
 * il hérite d'une formulation générique — mais il vaut mieux qu'il en ait une :
 * c'est le seul texte que rencontre un lecteur d'écran.
 */
const CONVERSATION_ALTS: readonly string[] = [
    "La fiche d'une chemise sur Bershka, à 29,99 €, telle qu'on la trouve avant de nous l'envoyer.",
    'Le lien du produit collé dans la conversation, puis la couleur, la taille et la quantité demandées.',
    'La fin de la demande : le pays et la ville de livraison, le numéro de téléphone, le nom, puis le récapitulatif.',
];

/**
 * Les écrans de la conversation, dans l'ordre, avec leur description.
 *
 * Le tri est numérique et non alphabétique : rangé comme du texte, un dixième
 * écran passerait devant le deuxième.
 */
export const conversationShots = (): { src: string; alt: string }[] =>
    Object.entries(FILES)
        .filter(([path]) => /\/conversation-\d+\.webp$/.test(path))
        .map(([path, src]) => ({
            rank: Number(path.match(/conversation-(\d+)\.webp$/)?.[1] ?? 0),
            src,
        }))
        .sort((a, b) => a.rank - b.rank)
        .map(({ rank, src }) => ({
            src,
            alt:
                CONVERSATION_ALTS[rank - 1] ??
                `Écran ${rank} de la conversation avec l'assistant Shoprelle.`,
        }));

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
