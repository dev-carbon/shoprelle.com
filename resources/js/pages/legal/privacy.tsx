import { LegalPage, LegalSection } from '@/components/legal-page';

type Props = {
    publisher: string;
    contactEmail: string;
    host: { name: string; address: string; website: string };
};

/**
 * La politique de confidentialité, écrite comme on l'expliquerait de vive
 * voix. Chaque affirmation correspond à un comportement réel du code (durée de
 * purge des captures, code d'accès haché, absence de traceurs) : si le code
 * change, cette page doit changer avec lui.
 */
export default function Confidentialite({ contactEmail, host }: Props) {
    return (
        <LegalPage title="Politique de confidentialité" updatedAt="5 août 2026">
            <LegalSection title="Ce que nous collectons">
                <p>
                    Quand vous passez une demande, nous enregistrons ce que vous
                    nous donnez : votre prénom et votre nom si vous les
                    indiquez, votre numéro de téléphone, le pays de livraison,
                    les liens des produits demandés, les captures d'écran que
                    vous joignez et les messages échangés avec l'assistant.
                </p>
                <p>
                    C'est tout. Pas de compte à créer, pas de mot de passe, pas
                    de données bancaires : les paiements se font en dehors du
                    site, directement avec nous.
                </p>
            </LegalSection>

            <LegalSection title="Pourquoi nous les collectons">
                <p>
                    Uniquement pour traiter votre demande : retrouver les
                    produits, établir votre devis, effectuer l'achat, organiser
                    la livraison et vous tenir au courant. Vos données ne sont
                    jamais vendues ni partagées avec qui que ce soit.
                </p>
            </LegalSection>

            <LegalSection title="Où elles sont stockées, et combien de temps">
                <p>
                    Vos données sont hébergées en Europe, chez {host.name}.
                    Elles sont conservées le temps de traiter votre demande,
                    puis comme historique de vos commandes — c'est ce qui vous
                    permet de retrouver vos demandes passées et vos devis.
                </p>
                <p>
                    Les captures d'écran envoyées pendant une conversation qui
                    n'aboutit pas à une demande sont supprimées automatiquement
                    sous 24 heures.
                </p>
            </LegalSection>

            <LegalSection title="Votre code d'accès">
                <p>
                    L'espace « Mes demandes » s'ouvre avec votre numéro de
                    téléphone et un code d'accès remis à votre première demande.
                    Ce code est stocké chiffré, comme un mot de passe :
                    personne, pas même nous, ne peut le relire. Si vous le
                    perdez, nous vous en remettons un nouveau.
                </p>
            </LegalSection>

            <LegalSection title="Cookies">
                <p>
                    Le site n'utilise que des cookies techniques de session,
                    nécessaires à son fonctionnement — garder votre conversation
                    avec l'assistant, vous reconnaître dans « Mes demandes ».
                    Aucun traceur publicitaire, aucun outil de mesure d'audience
                    tiers. C'est aussi pour cela qu'aucune bannière ne vous
                    demande votre consentement : il n'y a rien à consentir.
                </p>
            </LegalSection>

            <LegalSection title="Vos droits">
                <p>
                    Vous pouvez nous demander à tout moment de vous communiquer,
                    de corriger ou de supprimer les données que nous détenons
                    sur vous. Écrivez-nous à{' '}
                    <a
                        href={`mailto:${contactEmail}`}
                        className="text-foreground underline underline-offset-4"
                    >
                        {contactEmail}
                    </a>{' '}
                    depuis le numéro ou l'adresse liés à votre demande, et nous
                    nous en occupons.
                </p>
            </LegalSection>
        </LegalPage>
    );
}
