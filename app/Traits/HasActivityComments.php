<?php

namespace App\Traits;

trait HasActivityComments
{
    /**
     * Add a comment activity entry to this model.
     *
     * @param string $message
     * @return void
     */
    public function addComment(string $message): void
    {
        $signatureCollectionId = auth()->user()->signature_collection_id ?? null;
        activity()
            ->on($this)
            ->event('comment')
            ->withProperties(['signature_collection_id' => $signatureCollectionId])
            ->log($message);
    }
}
