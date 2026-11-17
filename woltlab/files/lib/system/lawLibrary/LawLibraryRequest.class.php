<?php
namespace wcf\system\lawLibrary;

use wcf\system\exception\IllegalLinkException;

/** Reject malformed HTTP values before the FormBuilder can retain field defaults. */
final class LawLibraryRequest
{
    public static function validatePost(array $fields, bool $requireRevision = false): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new IllegalLinkException();
        foreach ($_POST as $key => $value) {
            if (($key === 't' || \in_array($key, $fields, true) || \str_starts_with($key, 'before_')) && (!\is_string($value) || \str_contains($value, "\0"))) {
                throw new IllegalLinkException();
            }
        }
        if (isset($_REQUEST['t']) && !\is_string($_REQUEST['t'])) throw new IllegalLinkException();
        if ($requireRevision && (!\is_string($_POST['revision'] ?? null) || !\ctype_digit($_POST['revision']))) {
            throw new IllegalLinkException();
        }
    }
}
