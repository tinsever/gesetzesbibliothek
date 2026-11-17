<?php
namespace wcf\form;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\UserInputException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\lawLibrary\LawLibraryRequest;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

final class LawBookmarkForm extends AbstractForm
{
    public $loginRequired = true;
    public $neededPermissions = ['user.lawLibrary.canView'];
    public $useTemplate = false;
    private string $lawKey;
    private bool $isSaved;

    public function readParameters()
    {
        parent::readParameters();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new IllegalLinkException();
    }
    public function readFormParameters()
    {
        LawLibraryRequest::validatePost(['lawKey', 'saved']);
        parent::readFormParameters();
        if (!\is_string($_POST['lawKey'] ?? null) || !\in_array($_POST['saved'] ?? '', ['0', '1'], true)) throw new IllegalLinkException();
        $this->lawKey = $_POST['lawKey'];
        $this->isSaved = $_POST['saved'] === '1';
    }
    public function validate()
    {
        try {
            parent::validate();
        } catch (UserInputException $exception) {
            // This endpoint has no form template: report failure instead of an empty 200 response.
            throw new PermissionDeniedException();
        }
        if (!isset(LawLibraryRepository::numbered(LawLibraryRepository::read()['data'])[$this->lawKey])) throw new IllegalLinkException();
    }
    public function save()
    {
        parent::save();
        if ($this->isSaved) {
            $sql = 'INSERT IGNORE INTO wcf' . WCF_N . '_law_library_bookmark (userID, lawKey) VALUES (?, ?)';
        } else {
            $sql = 'DELETE FROM wcf' . WCF_N . '_law_library_bookmark WHERE userID = ? AND lawKey = ?';
        }
        WCF::getDB()->prepareStatement($sql)->execute([WCF::getUser()->userID, $this->lawKey]);
        $this->saved();
        return new RedirectResponse(LinkHandler::getInstance()->getLink('Law', ['lawKey' => $this->lawKey]), 303);
    }
}
