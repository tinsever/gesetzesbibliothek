<?php
namespace wcf\acp\page;

use wcf\page\AbstractPage;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\WCF;

final class LawCategoryListPage extends AbstractPage
{
    public $neededPermissions = ['admin.content.lawLibrary.canManage'];
    public $activeMenuItem = 'wcf.acp.menu.link.lawLibrary.categories';
    public $templateName = 'lawCategoryList';

    public function assignVariables()
    {
        parent::assignVariables();
        $data = LawLibraryRepository::read()['data'];
        $categories = $data['categories'];
        foreach ($categories as &$category) {
            $category['description'] = $category['description'] ?? '';
            $category['count'] = \count(\array_filter($data['laws'], static fn ($law) => $law['categoryId'] === $category['id']));
            $category['range'] = ($category['base'] + 1) . '–' . ($category['base'] + 99);
        }
        WCF::getTPL()->assign(['lawCategories' => $categories, 'saved' => isset($_GET['saved'])]);
    }
}
