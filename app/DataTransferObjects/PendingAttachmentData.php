<?php

namespace App\DataTransferObjects;

/**
 * A screenshot already written to the private disk but not yet bound to a
 * purchase request, because the conversation is still in progress.
 */
final readonly class PendingAttachmentData
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {}

    /**
     * @param  array{disk: string, path: string, original_name: string, mime_type: string, size: int}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            disk: $attributes['disk'],
            path: $attributes['path'],
            originalName: $attributes['original_name'],
            mimeType: $attributes['mime_type'],
            size: $attributes['size'],
        );
    }

    /**
     * @return array{disk: string, path: string, original_name: string, mime_type: string, size: int}
     */
    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
        ];
    }
}
