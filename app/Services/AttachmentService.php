<?php

namespace App\Services;

use App\DataTransferObjects\PendingAttachmentData;
use App\Exceptions\ConversationException;
use App\Models\Attachment;
use App\Models\PurchaseItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores customer screenshots on a private disk.
 *
 * Files are uploaded while the conversation is still running, so they are first
 * written to a per-conversation staging directory and only moved under the
 * request once it is confirmed.
 */
class AttachmentService
{
    /**
     * Persist an upload for an in-progress conversation.
     *
     * The stored filename is generated, never derived from user input, so a
     * crafted name cannot influence the path or the served extension.
     */
    public function storePending(UploadedFile $file, string $conversationId): PendingAttachmentData
    {
        $disk = config('shoprelle.attachments.disk');
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());

        $path = $file->storeAs(
            $this->pendingDirectory($conversationId),
            Str::uuid()->toString().'.'.$extension,
            ['disk' => $disk],
        );

        if ($path === false) {
            throw ConversationException::uploadFailed();
        }

        return new PendingAttachmentData(
            disk: $disk,
            path: $path,
            originalName: $this->sanitizeName($file->getClientOriginalName()),
            mimeType: $file->getMimeType() ?? 'application/octet-stream',
            size: $file->getSize() ?: 0,
        );
    }

    /**
     * Persist raw bytes for an in-progress conversation.
     *
     * Messaging channels hand us a downloaded payload rather than an HTTP
     * upload, so the MIME type is sniffed from the content itself — never from
     * anything the sender claimed.
     *
     * @throws ConversationException when the payload is not an accepted image
     */
    public function storePendingContents(
        string $contents,
        string $originalName,
        string $conversationId,
    ): PendingAttachmentData {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/heic' => 'heic',
        ];

        if (! is_string($mimeType) || ! array_key_exists($mimeType, $allowed)) {
            throw ConversationException::unsupportedImage();
        }

        $maxBytes = ((int) config('shoprelle.attachments.max_size_kilobytes')) * 1024;

        if (strlen($contents) > $maxBytes) {
            throw ConversationException::imageTooLarge();
        }

        $disk = config('shoprelle.attachments.disk');
        $path = $this->pendingDirectory($conversationId).'/'.Str::uuid()->toString().'.'.$allowed[$mimeType];

        Storage::disk($disk)->put($path, $contents);

        return new PendingAttachmentData(
            disk: $disk,
            path: $path,
            originalName: $this->sanitizeName($originalName),
            mimeType: $mimeType,
            size: strlen($contents),
        );
    }

    /**
     * Move a staged file under its request and record it.
     */
    public function attachToItem(PendingAttachmentData $pending, PurchaseItem $item): Attachment
    {
        $storage = Storage::disk($pending->disk);
        $destination = sprintf(
            '%s/%d/%s',
            config('shoprelle.attachments.directory'),
            $item->purchase_request_id,
            basename($pending->path),
        );

        if ($storage->exists($pending->path)) {
            $storage->move($pending->path, $destination);
        }

        return $item->attachments()->create([
            'purchase_request_id' => $item->purchase_request_id,
            'disk' => $pending->disk,
            'path' => $destination,
            'original_name' => $pending->originalName,
            'mime_type' => $pending->mimeType,
            'size' => $pending->size,
        ]);
    }

    /**
     * Discard the staging directory of a conversation that was reset or
     * abandoned, so unreferenced uploads do not accumulate.
     */
    public function discardPending(string $conversationId): void
    {
        Storage::disk(config('shoprelle.attachments.disk'))
            ->deleteDirectory($this->pendingDirectory($conversationId));
    }

    /**
     * Remove staging directories older than the given number of hours.
     *
     * @return int the number of directories removed
     */
    public function prunePending(int $olderThanHours): int
    {
        $disk = Storage::disk(config('shoprelle.attachments.disk'));
        $root = config('shoprelle.attachments.directory').'/pending';
        $threshold = now()->subHours($olderThanHours)->getTimestamp();
        $removed = 0;

        foreach ($disk->directories($root) as $directory) {
            $files = $disk->files($directory);
            $newest = 0;

            foreach ($files as $file) {
                $newest = max($newest, $disk->lastModified($file));
            }

            if ($files === [] || $newest < $threshold) {
                $disk->deleteDirectory($directory);
                $removed++;
            }
        }

        return $removed;
    }

    private function pendingDirectory(string $conversationId): string
    {
        return sprintf(
            '%s/pending/%s',
            config('shoprelle.attachments.directory'),
            $conversationId,
        );
    }

    /**
     * Keep the original name for display only, stripped of anything that could
     * be interpreted as a path or as markup.
     */
    private function sanitizeName(string $name): string
    {
        return Str::limit(preg_replace('/[^\w\-. ]/u', '', basename($name)) ?: 'capture', 120, '');
    }
}
