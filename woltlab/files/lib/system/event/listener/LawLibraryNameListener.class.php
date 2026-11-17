<?php
namespace wcf\system\event\listener;

use wcf\system\WCF;
use wcf\util\StringUtil;

/** Apply the configured display name without rewriting menu, page or law records. */
final class LawLibraryNameListener implements IParameterizedEventListener
{
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (!\defined('LAW_LIBRARY_NAME') || \trim(LAW_LIBRARY_NAME) === '') return;
        $language = WCF::getLanguage();
        $items = [
            'wcf.lawLibrary.title',
            'wcf.acp.menu.link.lawLibrary',
            'wcf.menu.item.de.eulenthal.wcf.lawLibrary.Library',
        ];
        // Load all categories first so later category loads cannot overwrite the name.
        foreach ($items as $item) $language->get($item);
        foreach ($items as $item) $language->overrideItem($item, $item === 'wcf.lawLibrary.title' ? StringUtil::encodeHTML(\trim(LAW_LIBRARY_NAME)) : \trim(LAW_LIBRARY_NAME));
    }
}
