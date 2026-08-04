<?php

namespace App\Chatbot;

/**
 * The steps of a Shoprelle conversation.
 *
 * A step declares what it asks for and how a channel should render the answer
 * control. The transitions themselves live in {@see ChatbotEngine}, so the flow
 * can be read top to bottom in a single place.
 */
enum Step: string
{
    // Entry point: what does the customer want to do?
    case Menu = 'menu';

    // Browsing the help subjects, one after another.
    case HelpTopic = 'help_topic';

    // New purchase request.
    case Marketplace = 'marketplace';
    case ProductUrl = 'product_url';
    case Color = 'color';
    case Size = 'size';
    case Quantity = 'quantity';
    case DeclaredPrice = 'declared_price';
    case Screenshot = 'screenshot';
    case ItemComment = 'item_comment';
    case MoreItems = 'more_items';
    case Country = 'country';
    case City = 'city';
    case Phone = 'phone';
    case FullName = 'full_name';
    case Email = 'email';
    case Summary = 'summary';
    case Completed = 'completed';

    // Track a request, or list the ones a phone number owns.
    case TrackReference = 'track_reference';
    case TrackPhone = 'track_phone';
    case MyOrdersPhone = 'my_orders_phone';
    case MyOrdersCode = 'my_orders_code';

    // Leave a review.
    case ReviewRating = 'review_rating';
    case ReviewComment = 'review_comment';

    // Écrire à l'équipe. Le message d'abord, le moyen de rappel ensuite : on
    // demande d'abord ce que la personne est venue dire, et seulement ensuite
    // comment la joindre — l'inverse fait un formulaire.
    case ContactMessage = 'contact_message';
    case ContactReply = 'contact_reply';

    /**
     * How a channel should render the answer control.
     */
    public function inputType(): InputType
    {
        return match ($this) {
            self::Menu, self::HelpTopic, self::Marketplace, self::Country, self::MoreItems, self::Summary,
            self::ReviewRating => InputType::Choice,
            self::ProductUrl => InputType::Url,
            self::Quantity => InputType::Number,
            self::DeclaredPrice => InputType::Decimal,
            self::Screenshot => InputType::File,
            self::ItemComment, self::ReviewComment, self::ContactMessage => InputType::LongText,
            self::Email => InputType::Email,
            self::Completed => InputType::None,
            default => InputType::Text,
        };
    }

    /**
     * Whether the customer may move on without answering.
     */
    public function isOptional(): bool
    {
        return match ($this) {
            self::Color, self::Size, self::DeclaredPrice, self::Screenshot, self::ItemComment,
            self::Email, self::ReviewComment,
            // Facultatif, et c'est un choix : quelqu'un qui pose sa question
            // sans laisser de numéro l'a quand même posée. Le message le dit à
            // ce moment-là plutôt que de la refuser.
            self::ContactReply => true,
            default => false,
        };
    }

    /**
     * Steps that describe the product currently being added, as opposed to the
     * customer or the request as a whole.
     */
    public function isItemStep(): bool
    {
        return match ($this) {
            self::Marketplace, self::ProductUrl, self::Color, self::Size,
            self::Quantity, self::DeclaredPrice, self::Screenshot, self::ItemComment => true,
            default => false,
        };
    }

    /**
     * Steps the engine cannot resolve on its own because they need the
     * database. {@see ConversationManager} intercepts these.
     */
    public function needsLookup(): bool
    {
        return match ($this) {
            // The phone alone no longer lists anything: it is only half of what
            // "Mes demandes" asks for, and the code that follows is what
            // actually triggers the lookup. The review is the other kind of
            // step the engine cannot finish — it writes rather than reads.
            self::TrackPhone, self::MyOrdersCode, self::ReviewComment,
            // Comme l'avis : cette étape écrit, et l'écriture appartient au
            // gestionnaire, pas au moteur.
            self::ContactReply => true,
            default => false,
        };
    }
}
