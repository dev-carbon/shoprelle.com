<?php

namespace App\Chatbot;

/**
 * The answer control the frontend renders for the current step.
 *
 * Keeping this on the server means a new channel (WhatsApp, Telegram) can map
 * the same descriptor onto its own widgets without duplicating the flow.
 */
enum InputType: string
{
    case Text = 'text';
    case LongText = 'long_text';
    case Url = 'url';
    case Number = 'number';
    case Decimal = 'decimal';
    case Email = 'email';
    case Choice = 'choice';
    case File = 'file';
    case None = 'none';
}
