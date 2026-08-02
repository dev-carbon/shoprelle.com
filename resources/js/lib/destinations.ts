/**
 * ISO 3166-1 numeric ids for the destinations, keyed by the alpha-2 codes
 * `config/shoprelle.php` uses.
 *
 * The atlases are keyed by the numeric code and the config by alpha-2, so the
 * two have to be joined on something, and the code is the only thing that will
 * not drift — their own names are in English and ours are not.
 *
 * An id with no shape is simply never looked up, so opening a destination stays
 * a line in `config/shoprelle.php` and nothing here.
 *
 * Kept in its own module rather than inside the map: the hero's backdrop lights
 * the same countries, and it is drawn eagerly while the map is loaded on
 * demand. Importing the table from there would drag the whole atlas into the
 * first paint.
 */
export const ISO_NUMERIC: Record<string, string> = {
    CM: '120', // Cameroun
    CI: '384', // Côte d'Ivoire
    SN: '686', // Sénégal
    GA: '266', // Gabon
    CG: '178', // Congo
};

/** France, where every order is bought and every parcel leaves from. */
export const ORIGIN_ID = '250';

/**
 * Which side a route bows to, for the destinations where geography gives a poor
 * answer.
 *
 * By default an arc bows away from the hub on the destination's own side —
 * west of Paris bows west, east of it bows east — which fans the routes evenly
 * and keeps any two of them from crossing. That rule reads the longitude and
 * nothing else, and longitude is a bad proxy for what a map looks like:
 * Cameroon sits a few degrees east of Paris and between Gabon and Congo, so the
 * rule sends the one open route out alone over Asia while every announced one
 * sweeps the Atlantic.
 *
 * Which side of a frame an arc should take is a drawing decision, not a fact
 * about a country, so it is stated rather than derived. A destination absent
 * from this table simply follows the default.
 */
export const ROUTE_SIDE: Record<string, 'west' | 'east'> = {
    CM: 'west',
};

/** The numeric ids for a list of alpha-2 codes, dropping any we cannot draw. */
export function toNumericIds(codes: string[]): string[] {
    return codes.map((code) => ISO_NUMERIC[code]).filter(Boolean);
}

/**
 * Le drapeau d'un pays, dérivé de son code alpha-2.
 *
 * Un drapeau emoji est la paire d'« indicateurs régionaux » correspondant aux
 * deux lettres du code — `CM` donne 🇨🇲 — donc il se calcule et n'a pas à être
 * stocké. Aucune table à tenir à jour : ouvrir une destination reste une ligne
 * dans `config/shoprelle.php`.
 *
 * ⚠️ Windows ne dessine pas les drapeaux : il affiche les deux lettres à la
 * place. Le badge y devient « CM Cameroun » — moins joli, mais lisible, et
 * jamais un carré vide. C'est pourquoi le nom du pays reste écrit à côté et
 * que le drapeau ne porte, seul, aucune information.
 */
export function flagFor(code: string): string {
    return String.fromCodePoint(
        ...[...code.toUpperCase()].map(
            (letter) => 0x1f1e6 + letter.charCodeAt(0) - 65,
        ),
    );
}
