<?php
namespace wcf\system\lawLibrary;

use wcf\system\html\output\HtmlOutputProcessor;

/** Format the processed output only; stored HTML and WoltLab's generated TOC stay intact. */
final class LawHtmlOutputProcessor extends HtmlOutputProcessor
{
    public function applyListStyle(string $style): void
    {
        if ($style !== 'upper-alpha') return;
        // The TOC is generated later by getHtml(), so it is not part of this document.
        $xpath = $this->getHtmlOutputNodeProcessor()->getXPath();
        foreach ($xpath->query('//ol[not(ancestor::ol) and not(ancestor::ul) and not(ancestor::blockquote)]') as $list) {
            $list->setAttribute('type', 'A');
            $list->setAttribute('style', 'list-style-type: upper-alpha');
        }
    }
}
