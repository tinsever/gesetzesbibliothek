<?php
namespace wcf\page;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\system\request\LinkHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\WCF;

final class LawLibraryPage extends AbstractPage
{
    public $neededPermissions = ['user.lawLibrary.canView'];
    public $templateName = 'lawLibrary';
    private array $snapshot;
    private string $categoryId = '';
    private string $query = '';
    private string $status = '';
    private string $view = 'all';

    public function readParameters()
    {
        parent::readParameters();
        $input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        foreach (['categoryId', 'query', 'status', 'view'] as $key) {
            if (isset($input[$key])) {
                if (!\is_string($input[$key])) throw new IllegalLinkException();
                $this->$key = \mb_substr(\trim($input[$key]), 0, 250);
            }
        }
        if (!\in_array($this->view, ['all', 'recent', 'bookmarks'], true)) $this->view = 'all';
        if (!\in_array($this->status, ['', 'active', 'announced', 'repealed'], true)) $this->status = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return new RedirectResponse(LinkHandler::getInstance()->getLink('LawLibrary', [
                'categoryId' => $this->categoryId, 'query' => $this->query,
                'status' => $this->status, 'view' => $this->view,
            ]), 303);
        }
    }

    public function readData()
    {
        parent::readData();
        $this->snapshot = LawLibraryRepository::read();
    }

    public function assignVariables()
    {
        parent::assignVariables();
        $data = $this->snapshot['data'];
        $laws = LawLibraryRepository::numbered($data);
        $bookmarks = LawLibraryRepository::bookmarks();
        $title = '';
        $description = '';
        foreach ($data['categories'] as $category) {
            if ($category['id'] === $this->categoryId) {
                $title = $category['title'];
                $description = $category['description'] ?? '';
            }
        }
        if ($this->categoryId !== '' && $title === '') throw new IllegalLinkException();
        $laws = \array_filter($laws, function ($law) use ($bookmarks) {
            if ($this->query === '' && $this->view === 'all' && $this->categoryId !== '' && $law['categoryId'] !== $this->categoryId) return false;
            if ($this->status !== '' && $law['status'] !== $this->status) return false;
            if ($this->view === 'bookmarks' && !\in_array($law['id'], $bookmarks, true)) return false;
            return $this->query === '' || \mb_stripos($law['number'] . ' ' . $law['title'] . ' ' . $law['abbreviation'] . ' ' . \html_entity_decode(\strip_tags($law['content']), ENT_QUOTES, 'UTF-8'), $this->query) !== false;
        });
        if ($this->view === 'recent') \uasort($laws, static fn ($a, $b) => \strcmp($b['updated'], $a['updated']));
        LawLibraryRepository::assignSidebar($data, $this->categoryId);
        WCF::getTPL()->assign(['laws' => $laws, 'lawCategoryTitle' => $title, 'lawCategoryDescription' => $description, 'query' => $this->query, 'lawStatus' => $this->status, 'lawView' => $this->view, 'lawBookmarks' => $bookmarks]);
    }
}
