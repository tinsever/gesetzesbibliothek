<?php
namespace wcf\system\lawLibrary;

/**
 * An ordered collection. Numbers are derived from category base + position;
 * IDs are independent of position and remain stable across every mutation.
 */
final class LawLibrary
{
    public static function apply(array $data, array $command, string $today): array
    {
        if (($command['action'] ?? '') === 'setListStyle') {
            $index = \array_search($command['lawKey'] ?? null, \array_column($data['laws'], 'id'), true);
            if ($index === false) throw new \InvalidArgumentException('lawKey');
            self::validateListStyle($command['listStyle'] ?? null);
            $data['laws'][$index]['listStyle'] = $command['listStyle'];
            return $data;
        }
        if (\in_array($command['action'] ?? '', ['saveAmendment', 'deleteAmendment'], true)) {
            return self::changeAmendment($data, $command);
        }
        if (\in_array($command['action'] ?? '', ['saveCategory', 'deleteCategory'], true)) {
            return self::changeCategory($data, $command);
        }
        if (($command['action'] ?? '') === 'move') {
            $id = $command['id'] ?? null;
            $direction = $command['direction'] ?? null;
            if (!\is_string($id) || !\in_array($direction, [-1, 1], true)) {
                throw new \InvalidArgumentException('Ungültige Verschiebung.');
            }
            $index = \array_search($id, \array_column($data['laws'], 'id'), true);
            if ($index === false) throw new \InvalidArgumentException('Gesetz nicht gefunden.');
            $categoryId = $data['laws'][$index]['categoryId'];
            $indices = \array_keys(\array_filter($data['laws'], static fn ($law) => $law['categoryId'] === $categoryId));
            $position = \array_search($index, $indices, true);
            $target = $indices[$position + $direction] ?? null;
            if ($target !== null) {
                [$data['laws'][$index], $data['laws'][$target]] = [$data['laws'][$target], $data['laws'][$index]];
            }
            return $data;
        }
        if (($command['action'] ?? '') !== 'save' || !\is_array($command['law'] ?? null)) {
            throw new \InvalidArgumentException('Unbekannte Aktion.');
        }
        $input = $command['law'];
        $existingIndex = \array_search($input['id'] ?? null, \array_column($data['laws'], 'id'), true);
        $existing = $existingIndex === false ? [] : $data['laws'][$existingIndex];
        // Preserve optional and future metadata when older forms omit it.
        $law = $existing;
        if (\array_key_exists('listStyle', $input)) {
            self::validateListStyle($input['listStyle']);
            $law['listStyle'] = $input['listStyle'];
        }
        foreach (['id' => 80, 'categoryId' => 80, 'title' => 250, 'abbreviation' => 30, 'date' => 10, 'status' => 20, 'content' => 200000] as $field => $limit) {
            if (!\is_string($input[$field] ?? null) || \mb_strlen($input[$field]) > $limit) {
                throw new \InvalidArgumentException('Ungültiger oder zu langer Wert: ' . $field);
            }
            $law[$field] = \trim($input[$field]);
        }
        if (!\preg_match('/\A[a-zA-Z0-9_-]{1,80}\z/D', $law['id'])) {
            throw new \InvalidArgumentException('Ungültige Gesetzes-ID.');
        }
        if ($law['title'] === '' || $law['content'] === '') {
            throw new \InvalidArgumentException('Titel und Gesetzestext sind erforderlich.');
        }
        if (!\in_array($law['status'], ['active', 'announced', 'repealed'], true)) {
            throw new \InvalidArgumentException('Ungültiger Status.');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $law['date']);
        if (!$date || $date->format('Y-m-d') !== $law['date'] || (int) $date->format('Y') < 1) {
            throw new \InvalidArgumentException('Ungültiges Datum.');
        }
        if (!\in_array($law['categoryId'], \array_column($data['categories'], 'id'), true)) {
            throw new \InvalidArgumentException('Unbekanntes Rechtsgebiet.');
        }
        $law['updated'] = $input['updated'] ?? $existing['updated'] ?? $today;
        if (!self::validDate($law['updated'])) throw new \InvalidArgumentException('updated');
        foreach ($law['amendments'] ?? [] as $amendment) {
            if ($amendment['date'] > $law['updated']) throw new \InvalidArgumentException('updatedBeforeHistory');
        }
        $source = $input['amendingLawKey'] ?? '';
        if (!\is_string($source)) throw new \InvalidArgumentException('amendingLawKey');
        if ($source !== '') {
            self::validateSource($data, $law['id'], $source);
            $amendments = $law['amendments'] ?? [];
            $duplicate = false;
            foreach ($amendments as $amendment) {
                if ($amendment['date'] === $law['updated'] && $amendment['lawKey'] === $source) $duplicate = true;
            }
            if (!$duplicate) $amendments[] = ['id' => \bin2hex(\random_bytes(16)), 'date' => $law['updated'], 'lawKey' => $source];
            $law['amendments'] = $amendments;
        }
        $law['format'] = ($input['format'] ?? 'plain') === 'html' ? 'html' : 'plain';
        $beforeId = $command['beforeId'] ?? '';
        if (!\is_string($beforeId) || $beforeId === $law['id']) {
            throw new \InvalidArgumentException('Ungültige Einfügeposition.');
        }
        $data['laws'] = \array_values(\array_filter($data['laws'], static fn ($entry) => $entry['id'] !== $law['id']));
        $siblings = \array_filter($data['laws'], static fn ($entry) => $entry['categoryId'] === $law['categoryId']);
        if (\count($siblings) >= 99) {
            throw new \InvalidArgumentException('Ein Rechtsgebiet kann höchstens 99 Gesetze enthalten.');
        }
        if ($beforeId !== '') {
            $index = \array_search($beforeId, \array_column($data['laws'], 'id'), true);
            if ($index === false || $data['laws'][$index]['categoryId'] !== $law['categoryId']) {
                throw new \InvalidArgumentException('Die Einfügeposition ist nicht mehr verfügbar.');
            }
            \array_splice($data['laws'], $index, 0, [$law]);
        } else {
            $data['laws'][] = $law;
        }
        return $data;
    }

    public static function validDate(mixed $value): bool
    {
        if (!\is_string($value) || !\preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/D', $value)) return false;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value && (int) $date->format('Y') >= 1;
    }

    private static function validateListStyle(mixed $value): void
    {
        if (!\in_array($value, ['default', 'upper-alpha'], true)) throw new \InvalidArgumentException('listStyle');
    }

    private static function validateSource(array $data, string $lawKey, mixed $source): void
    {
        if (!\is_string($source) || $source === $lawKey || !\in_array($source, \array_column($data['laws'], 'id'), true)) {
            throw new \InvalidArgumentException('amendingLawKey');
        }
    }

    private static function changeAmendment(array $data, array $command): array
    {
        $index = \array_search($command['lawKey'] ?? null, \array_column($data['laws'], 'id'), true);
        if ($index === false) throw new \InvalidArgumentException('lawKey');
        $law = &$data['laws'][$index];
        $id = $command['id'] ?? null;
        if (!\is_string($id) || !\preg_match('/\A[a-zA-Z0-9_-]{1,80}\z/D', $id)) throw new \InvalidArgumentException('amendment');
        $amendments = $law['amendments'] ?? [];
        $entryIndex = \array_search($id, \array_column($amendments, 'id'), true);
        if ($command['action'] === 'deleteAmendment') {
            if ($entryIndex === false) throw new \InvalidArgumentException('amendment');
            \array_splice($amendments, $entryIndex, 1);
        } else {
            if (!self::validDate($command['date'] ?? null)) throw new \InvalidArgumentException('date');
            self::validateSource($data, $law['id'], $command['amendingLawKey'] ?? null);
            foreach ($amendments as $entry) {
                if ($entry['id'] !== $id && $entry['date'] === $command['date'] && $entry['lawKey'] === $command['amendingLawKey']) {
                    throw new \InvalidArgumentException('duplicateAmendment');
                }
            }
            $entry = ['id' => $id, 'date' => $command['date'], 'lawKey' => $command['amendingLawKey']];
            if ($entryIndex === false) $amendments[] = $entry;
            else $amendments[$entryIndex] = \array_replace($amendments[$entryIndex], $entry);
            if ($entry['date'] > $law['updated']) $law['updated'] = $entry['date'];
        }
        $law['amendments'] = $amendments;
        return $data;
    }

    private static function changeCategory(array $data, array $command): array
    {
        $id = $command['id'] ?? null;
        if (!\is_string($id) || !\preg_match('/\A[a-zA-Z0-9_-]{1,80}\z/D', $id)) {
            throw new \InvalidArgumentException('category');
        }
        $index = \array_search($id, \array_column($data['categories'], 'id'), true);
        if ($command['action'] === 'deleteCategory') {
            if ($index === false) throw new \InvalidArgumentException('category');
            if (\count($data['categories']) === 1) throw new \InvalidArgumentException('lastCategory');
            foreach ($data['laws'] as $law) {
                if ($law['categoryId'] === $id) throw new \InvalidArgumentException('categoryNotEmpty');
            }
            \array_splice($data['categories'], $index, 1);
            return $data;
        }
        $title = $command['title'] ?? null;
        $description = $command['description'] ?? '';
        $base = $command['base'] ?? null;
        if (!\is_string($title) || \trim($title) === '' || \mb_strlen($title) > 250) throw new \InvalidArgumentException('title');
        if (!\is_string($description) || \mb_strlen($description) > 2000) throw new \InvalidArgumentException('description');
        if (!\is_int($base) || $base < 100 || $base > 999900 || $base % 100 !== 0) throw new \InvalidArgumentException('base');
        foreach ($data['categories'] as $category) {
            if ($category['id'] !== $id && $category['base'] === $base) throw new \InvalidArgumentException('duplicateBase');
        }
        $category = $index === false ? [] : $data['categories'][$index];
        $category = \array_replace($category, ['id' => $id, 'title' => \trim($title), 'description' => \trim($description), 'base' => $base]);
        $categories = \array_values(\array_filter($data['categories'], static fn ($entry) => $entry['id'] !== $id));
        if (\count($categories) >= 99) throw new \InvalidArgumentException('categoryLimit');
        $beforeId = $command['beforeId'] ?? '';
        if (!\is_string($beforeId) || $beforeId === $id) throw new \InvalidArgumentException('beforeId');
        if ($beforeId === '') {
            $categories[] = $category;
        } else {
            $before = \array_search($beforeId, \array_column($categories, 'id'), true);
            if ($before === false) throw new \InvalidArgumentException('beforeId');
            \array_splice($categories, $before, 0, [$category]);
        }
        $data['categories'] = $categories;
        return $data;
    }

}
