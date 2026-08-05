import { LegalPage, LegalSection } from '@/components/legal-page';

type Props = {
    publisher: string;
    publisherEmail: string;
    developer: string;
    developerEmail: string;
    contactEmail: string;
    host: { name: string; address: string; website: string };
};

/**
 * Les mentions légales, réduites à ce qu'elles doivent dire : qui édite, qui
 * héberge, comment nous joindre, et ce que Shoprelle est — un service
 * d'accompagnement d'achat indépendant des plateformes qu'il cite.
 */
export default function MentionsLegales({
    publisher,
    publisherEmail,
    developer,
    developerEmail,
    contactEmail,
    host,
}: Props) {
    return (
        <LegalPage title="Mentions légales" updatedAt="5 août 2026">
            <LegalSection title="Éditeur du site">
                <p>
                    Le site shoprelle.com est édité par {publisher} —{' '}
                    <a
                        href={`mailto:${publisherEmail}`}
                        className="text-foreground underline underline-offset-4"
                    >
                        {publisherEmail}
                    </a>
                    .
                    <br />
                    Contact général :{' '}
                    <a
                        href={`mailto:${contactEmail}`}
                        className="text-foreground underline underline-offset-4"
                    >
                        {contactEmail}
                    </a>
                </p>
                <p>
                    Site conçu et développé par {developer} —{' '}
                    <a
                        href={`mailto:${developerEmail}`}
                        className="text-foreground underline underline-offset-4"
                    >
                        {developerEmail}
                    </a>
                    .
                </p>
            </LegalSection>

            <LegalSection title="Hébergement">
                <p>
                    Le site est hébergé par {host.name}, {host.address} —{' '}
                    <a
                        href={host.website}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-foreground underline underline-offset-4"
                    >
                        {host.website.replace('https://www.', '')}
                    </a>
                    .
                </p>
            </LegalSection>

            <LegalSection title="Ce qu'est Shoprelle">
                <p>
                    Shoprelle est un service d'accompagnement d'achat en ligne :
                    vous nous envoyez le lien d'un produit, nous l'achetons pour
                    vous et nous organisons sa livraison. Les prix affichés sur
                    le site sont indicatifs — seul le devis que nous vous
                    envoyons engage.
                </p>
                <p>
                    Shoprelle est indépendant des plateformes citées sur le site
                    (Shein, Amazon, AliExpress et les autres). Leurs noms et
                    logos appartiennent à leurs propriétaires respectifs et ne
                    sont utilisés que pour identifier les boutiques sur
                    lesquelles nous pouvons acheter pour vous.
                </p>
            </LegalSection>

            <LegalSection title="Contenus">
                <p>
                    Les textes, photographies et éléments graphiques de ce site
                    ne peuvent pas être réutilisés sans notre accord. Pour toute
                    demande, écrivez-nous à l'adresse ci-dessus.
                </p>
            </LegalSection>
        </LegalPage>
    );
}
