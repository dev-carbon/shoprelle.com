/**
 * Domain types mirroring the payloads the Laravel side sends.
 *
 * Statuses and marketplaces are plain strings rather than unions: they come from
 * PHP enums, and the server always ships the label and the colour alongside the
 * value, so the frontend never has to know the full list.
 */

export type Option = {
    value: string;
    label: string;
};

export type StatusOption = Option & {
    color: string;
};

export type ChatAuthor = 'bot' | 'customer';

export type ChatMessage = {
    id: string;
    author: ChatAuthor;
    text: string;
    at: string;
};

export type ChatInputType =
    | 'text'
    | 'long_text'
    | 'url'
    | 'number'
    | 'decimal'
    | 'email'
    | 'choice'
    | 'file'
    | 'none';

export type ChatMilestone = {
    label: string;
    state: 'done' | 'current' | 'todo';
};

export type ChatProgress = {
    current: number;
    total: number;
    milestones: ChatMilestone[];
};

export type ChatStep = {
    step: string;
    input_type: ChatInputType;
    optional: boolean;
    placeholder: string | null;
    /** Characters the step accepts, mirroring the server-side validator. */
    max_length: number | null;
    options: Option[];
    item_number: number;
    progress: ChatProgress | null;
};

export type ChatSummaryItem = {
    marketplace: string;
    marketplace_label: string;
    product_url: string;
    quantity: number;
    color: string | null;
    size: string | null;
    declared_price: string | null;
    declared_currency: string | null;
    comment: string | null;
    attachment_count: number;
};

export type ChatSummary = {
    items: ChatSummaryItem[];
    customer: {
        full_name: string;
        phone: string | null;
        email: string | null;
        country: string | null;
        country_label: string | null;
        city: string | null;
    };
};

export type Conversation = {
    id: string;
    messages: ChatMessage[];
    current: ChatStep;
    summary: ChatSummary | null;
    intent: string | null;
    item_count: number;
    attachment_count: number;
    reference: string | null;
    completed: boolean;
};

export type PurchaseRequestRow = {
    reference: string;
    status: string;
    status_label: string;
    status_color: string;
    customer_name: string;
    customer_phone: string;
    country: string;
    country_label: string;
    city: string;
    marketplaces: string[];
    item_count: number;
    total_quantity: number;
    quote_total_amount: string | null;
    quote_currency: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type Attachment = {
    id: number;
    name: string;
    size: number;
    mime_type: string;
    url: string;
};

export type PurchaseItem = {
    id: number;
    marketplace: string;
    marketplace_label: string;
    product_url: string;
    product_name: string | null;
    quantity: number;
    color: string | null;
    size: string | null;
    variant: string | null;
    declared_price: string | null;
    declared_currency: string | null;
    quoted_amount: string | null;
    comment: string | null;
    attachments: Attachment[];
};

export type StatusHistoryEntry = {
    id: number;
    from: string | null;
    from_label: string | null;
    to: string;
    to_label: string;
    to_color: string;
    author: string | null;
    comment: string | null;
    created_at: string | null;
};

export type AdminNote = {
    id: number;
    body: string;
    author: string;
    created_at: string | null;
};

export type PurchaseRequestDetail = {
    reference: string;
    status: string;
    status_label: string;
    status_color: string;
    channel: string;
    channel_label: string;
    /** By what means a quote reaches this customer. Empty: by none. */
    delivery_channels: string[];
    customer_comment: string | null;
    created_at: string | null;
    updated_at: string | null;
    customer: {
        first_name: string;
        last_name: string;
        full_name: string;
        phone: string;
        email: string | null;
    };
    destination: {
        country: string;
        country_label: string;
        city: string;
    };
    quote: {
        items_amount: string;
        shipping_amount: string;
        total_amount: string;
        currency: string;
        /** What the goods cost abroad. Back-office only, never shown to the customer. */
        cost_amount: string | null;
        cost_currency: string | null;
        exchange_rate: string | null;
        /** Null unless both the cost and the rate were recorded. */
        margin_amount: string | null;
        notes: string | null;
        sent_at: string | null;
    } | null;
    /** How to hand the quote over by hand. Null until a quote exists. */
    handover: {
        message: string;
        whatsapp_url: string;
    } | null;
    /** Null until a quote exists: there is nothing to settle before one. */
    payments: {
        currency: string;
        total_paid: string;
        /** Negative once the customer has overpaid. */
        balance: string | null;
        is_settled: boolean;
        entries: PaymentEntry[];
    } | null;
    items: PurchaseItem[];
    status_history: StatusHistoryEntry[];
    notes: AdminNote[];
};

export type PaymentEntry = {
    id: number;
    amount: string;
    currency: string;
    method: string;
    method_label: string;
    provider: string | null;
    provider_reference: string | null;
    received_at: string;
    recorded_by: string | null;
    notes: string | null;
};

export type DashboardStatistics = {
    total: number;
    active: number;
    by_status: Record<string, number>;
    last_seven_days: number;
};

export type CustomerRow = {
    id: number;
    full_name: string;
    phone: string;
    email: string | null;
    country: string;
    country_label: string;
    city: string;
    request_count: number;
    last_request_at: string | null;
    created_at: string | null;
};

export type ReviewRow = {
    id: number;
    rating: number;
    comment: string | null;
    channel: string;
    channel_label: string;
    is_approved: boolean;
    created_at: string | null;
    /** Null whenever the reviewer was never identified, which is the norm. */
    customer: { id: number; full_name: string } | null;
    reference: string | null;
};

export type CustomerDetail = {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    phone: string;
    email: string | null;
    country: string;
    country_label: string;
    city: string;
    created_at: string | null;
    /** Whether a code exists. The code itself is hashed and never sent. */
    has_access_code: boolean;
    summary: {
        request_count: number;
        active_count: number;
        quoted_total: string;
        quote_currency: string;
    };
    requests: {
        reference: string;
        status: string;
        status_label: string;
        status_color: string;
        item_count: number;
        city: string;
        quote_total_amount: string | null;
        quote_currency: string | null;
        created_at: string | null;
    }[];
};

export type LabelledCount = {
    label: string;
    count: number;
};

export type Statistics = {
    period_days: number;
    headline: {
        requests_in_period: number;
        customers_total: number;
        new_customers_in_period: number;
        items_total: number;
        quoted_total: string;
        average_quote: string;
        currency: string;
    };
    traffic: {
        views: number;
        visitors: number;
        daily: { date: string; label: string; count: number }[];
    };
    daily: { date: string; label: string; count: number }[];
    funnel: (LabelledCount & { share: number })[];
    by_status: LabelledCount[];
    top_marketplaces: LabelledCount[];
    top_cities: LabelledCount[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
};

export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    /** Numbered page links, framed by the previous and next arrows. */
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
};

export type Paginated<T> = {
    data: T[];
    /** Shortcuts only — the numbered links live on `meta.links`. */
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: PaginationMeta;
};
