<?php
namespace wcf\system\lawLibrary;

use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;
use wcf\system\request\RequestHandler;

final class LawLibraryRepository
{
    public static function read(): array
    {
        WCF::getSession()->checkPermissions([RequestHandler::getInstance()->isACPRequest()
            ? 'admin.content.lawLibrary.canManage' : 'user.lawLibrary.canView']);
        $statement = WCF::getDB()->prepareStatement('SELECT revision, payload FROM wcf' . WCF_N . '_law_library WHERE libraryID = ?');
        $statement->execute([1]);
        $row = $statement->fetchArray();
        if (!$row) throw new IllegalLinkException();
        return ['revision' => (int) $row['revision'], 'data' => \json_decode($row['payload'], true, 32, JSON_THROW_ON_ERROR)];
    }

    public static function canManage(): bool
    {
        return WCF::getUser()->userID > 0
            && WCF::getSession()->getPermission('admin.general.canUseAcp')
            && WCF::getSession()->getPermission('admin.content.lawLibrary.canManage');
    }

    public static function numbered(array $data): array
    {
        $result = [];
        foreach ($data['categories'] as $category) {
            $number = $category['base'];
            foreach ($data['laws'] as $law) {
                if ($law['categoryId'] !== $category['id']) continue;
                $law['number'] = ++$number;
                $law['categoryTitle'] = $category['title'];
                $law['dateFormatted'] = \implode('.', \array_reverse(\explode('-', $law['date'])));
                $law['updatedFormatted'] = \implode('.', \array_reverse(\explode('-', $law['updated'])));
                $result[$law['id']] = $law;
            }
        }
        foreach ($result as &$law) {
            $law['amendments'] = $law['amendments'] ?? [];
            \usort($law['amendments'], static fn ($a, $b) => \strcmp($b['date'], $a['date']));
            $law['latestAmendments'] = [];
            foreach ($law['amendments'] as &$amendment) {
                $source = $result[$amendment['lawKey']] ?? null;
                $amendment['title'] = $source['title'] ?? $amendment['lawKey'];
                $amendment['number'] = $source['number'] ?? '';
                $amendment['dateFormatted'] = \implode('.', \array_reverse(\explode('-', $amendment['date'])));
                if ($amendment['date'] === $law['updated']) $law['latestAmendments'][] = $amendment;
            }
            unset($amendment);
        }
        unset($law);
        return $result;
    }

    public static function save(array $command, int $revision): array
    {
        if (!RequestHandler::getInstance()->isACPRequest() || !self::canManage()) throw new PermissionDeniedException();
        $snapshot = self::read();
        if ($snapshot['revision'] !== $revision) throw new \RuntimeException('conflict');
        $data = LawLibrary::apply($snapshot['data'], $command, \gmdate('Y-m-d'));
        $payload = \json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (\strlen($payload) > 12000000) throw new \InvalidArgumentException('capacity');
        $statement = WCF::getDB()->prepareStatement('UPDATE wcf' . WCF_N . '_law_library SET payload = ?, revision = revision + 1, updatedAt = ?, updatedBy = ? WHERE libraryID = ? AND revision = ?');
        $statement->execute([$payload, TIME_NOW, WCF::getUser()->userID, 1, $revision]);
        if ($statement->getAffectedRows() !== 1) throw new \RuntimeException('conflict');
        return $data;
    }

    public static function bookmarks(): array
    {
        if (!WCF::getUser()->userID) return [];
        $statement = WCF::getDB()->prepareStatement('SELECT lawKey FROM wcf' . WCF_N . '_law_library_bookmark WHERE userID = ?');
        $statement->execute([WCF::getUser()->userID]);
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function assignSidebar(array $data, string $categoryId = '', string $lawKey = ''): void
    {
        $laws = self::numbered($data);
        $categories = $data['categories'];
        foreach ($categories as &$category) {
            $category['prefix'] = (string) ($category['base'] / 100) . 'xx';
            $category['laws'] = \array_values(\array_filter($laws, static fn ($law) => $law['categoryId'] === $category['id']));
        }
        $name = \defined('LAW_LIBRARY_NAME') ? \trim(LAW_LIBRARY_NAME) : '';
        WCF::getTPL()->assign(['lawLibraryName' => $name !== '' ? $name : WCF::getLanguage()->get('wcf.lawLibrary.title'), 'lawCategories' => $categories, 'selectedCategory' => $categoryId, 'selectedLawKey' => $lawKey, 'canManageLaws' => self::canManage()]);
    }
}
