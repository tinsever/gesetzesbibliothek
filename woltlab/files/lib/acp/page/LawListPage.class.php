<?php
namespace wcf\acp\page;

use wcf\page\AbstractPage;
use wcf\system\exception\IllegalLinkException;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\WCF;

final class LawListPage extends AbstractPage
{
    public $neededPermissions = ['admin.content.lawLibrary.canManage'];
    public $activeMenuItem = 'wcf.acp.menu.link.lawLibrary.laws';
    public $templateName = 'lawList';
    private string $categoryId = '';

    public function readParameters()
    {
        parent::readParameters();
        $id = $_GET['categoryId'] ?? '';
        if (!\is_string($id)) throw new IllegalLinkException();
        $this->categoryId = $id;
    }

    public function assignVariables()
    {
        parent::assignVariables();
        $data = LawLibraryRepository::read()['data'];
        if ($this->categoryId !== '' && !\in_array($this->categoryId, \array_column($data['categories'], 'id'), true)) throw new IllegalLinkException();
        $laws = LawLibraryRepository::numbered($data);
        if ($this->categoryId !== '') $laws = \array_filter($laws, fn ($law) => $law['categoryId'] === $this->categoryId);
        WCF::getTPL()->assign(['laws' => $laws, 'selectedCategory' => $this->categoryId, 'saved' => isset($_GET['saved'])]);
    }
}
