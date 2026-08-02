<?php

namespace App\Chatbot;

/**
 * The subjects the assistant can explain from the help menu.
 *
 * Answers are plain text with no markup: the same string is read on the web
 * page and pushed to Telegram, so anything channel-specific would have to be
 * translated twice.
 */
enum HelpTopic: string
{
    case HowItWorks = 'how_it_works';
    case Fees = 'fees';
    case Delays = 'delays';
    case Sites = 'sites';
    case Changes = 'changes';

    public function label(): string
    {
        return match ($this) {
            self::HowItWorks => '💡 Comment ça marche',
            self::Fees => '💶 Frais et paiement',
            self::Delays => '🚚 Délais',
            self::Sites => '🛍️ Sites acceptés',
            self::Changes => '✏️ Modifier ou annuler',
        };
    }

    public function answer(): string
    {
        return match ($this) {
            self::HowItWorks => implode("\n", [
                'ℹ️ Shoprelle achète pour vous sur les sites qui ne livrent pas chez vous, puis vous réexpédie le colis.',
                '',
                '1. Vous nous envoyez le lien du produit et vos préférences.',
                '2. Nous achetons le produit en France et regroupons vos colis.',
                '3. Nous expédions vers votre ville et vous payez le devis.',
                '',
                'Aucun compte n\'est nécessaire : conservez simplement la référence de votre demande.',
            ]),
            self::Fees => implode("\n", [
                '💶 Le devis comprend deux lignes : le prix des produits, et le transport calculé au poids réel du colis.',
                '',
                'Vous recevez ce devis avant tout paiement, et nous n\'achetons rien tant que vous ne l\'avez pas validé.',
                'Plusieurs produits commandés ensemble partent dans un seul envoi : vous ne payez pas le transport deux fois.',
            ]),
            self::Delays => implode("\n", [
                '🚚 Le délai dépend du vendeur et de votre ville de livraison.',
                '',
                'Nous vous donnons une estimation avec le devis, une fois la disponibilité du produit vérifiée.',
                'Vous pouvez suivre l\'avancement à tout moment avec votre référence, depuis « Suivre ma demande ».',
            ]),
            self::Sites => implode("\n", [
                '🛍️ Shein, Temu, Amazon, AliExpress, Zara, ASOS, H&M, Nike, Sephora, Decathlon…',
                '',
                'Votre site n\'est pas dans la liste ? Envoyez quand même le lien : nous regardons si la commande est possible.',
            ]),
            self::Changes => implode("\n", [
                '✏️ Tant que le devis n\'est pas validé, rien n\'est acheté : écrivez-nous et nous corrigeons la demande.',
                '',
                'Gardez votre référence sous la main, c\'est elle qui nous permet de retrouver votre dossier.',
            ]),
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
