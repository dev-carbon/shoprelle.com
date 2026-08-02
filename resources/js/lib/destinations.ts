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

/** The numeric ids for a list of alpha-2 codes, dropping any we cannot draw. */
export function toNumericIds(codes: string[]): string[] {
    return codes.map((code) => ISO_NUMERIC[code]).filter(Boolean);
}
