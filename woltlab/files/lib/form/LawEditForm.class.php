<?php
namespace wcf\form;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\page\AbstractPage;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\request\LinkHandler;

/** Retains bookmarks to the editor from versions 1.0 and 1.1. */
final class LawEditForm extends AbstractPage
{
    public $loginRequired = true;
    public $neededPermissions = ['admin.content.lawLibrary.canManage'];

    public function readParameters()
    {
        parent::readParameters();
        if (!LawLibraryRepository::canManage()) throw new PermissionDeniedException();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new IllegalLinkException();
        $parameters = ['isACP' => true];
        foreach (['lawKey', 'categoryId'] as $key) {
            if (isset($_GET[$key])) {
                if (!\is_string($_GET[$key])) throw new IllegalLinkException();
                $parameters[$key] = $_GET[$key];
            }
        }
        return new RedirectResponse(LinkHandler::getInstance()->getLink('LawEdit', $parameters), 302);
    }
}
