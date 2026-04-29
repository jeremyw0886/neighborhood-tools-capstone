<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Category
{
    private const array ALLOWED_SORTS = ['category_name_cat', 'file_name_vec'];
    private const string DEFAULT_SORT = 'category_name_cat';
    private const string DEFAULT_DIR  = 'ASC';

    /**
     * Fetch all categories with tool counts, ratings, and fee ranges.
     *
     * Queries category_summary_fast_v (materialized, refreshed hourly) for
     * performant reads on the high-traffic category browse page.
     *
     * @return array
     */
    public static function getAll(): array
    {
        $pdo = Database::connection();

        return $pdo->query("
            SELECT *
            FROM category_summary_fast_v
            ORDER BY category_name_cat ASC
        ")->fetchAll();
    }

    /** @return array Categories matching the search term by name. */
    public static function adminSearch(string $term, int $limit = 5): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                id_cat,
                category_name_cat,
                total_tools,
                available_tools
            FROM category_summary_fast_v
            WHERE category_name_cat LIKE CONCAT('%', :term, '%')
            ORDER BY category_name_cat ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':term', Database::escapeLike($term));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array{id_cat: int, category_name_cat: string}>
     */
    public static function getList(): array
    {
        $pdo = Database::connection();

        return $pdo->query("
            SELECT id_cat, category_name_cat
            FROM category_cat
            ORDER BY category_name_cat ASC
        ")->fetchAll();
    }

    public static function getCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query("
            SELECT COUNT(*) FROM category_cat
        ")->fetchColumn();
    }

    public static function getAllWithIcons(): array
    {
        $pdo = Database::connection();

        return $pdo->query("
            SELECT c.id_cat,
                   c.category_name_cat,
                   c.id_vec_cat,
                   v.file_name_vec,
                   v.description_text_vec
            FROM category_cat c
            LEFT JOIN vector_image_vec v ON c.id_vec_cat = v.id_vec
            ORDER BY c.category_name_cat ASC
        ")->fetchAll();
    }

    /**
     * Count categories matching optional search and icon-assignment filter.
     *
     * @param  ?string $search  Category name substring
     * @param  ?bool   $hasIcon true = with icon, false = without icon, null = all
     * @return int
     */
    public static function getFilteredCount(?string $search, ?bool $hasIcon): int
    {
        $pdo = Database::connection();

        $filter = self::buildFilterWhere($search, $hasIcon);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM category_cat c LEFT JOIN vector_image_vec v ON c.id_vec_cat = v.id_vec "
            . $filter['sql']
        );

        foreach ($filter['params'] as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Build the shared WHERE clause used by getFilteredCount() and getAllWithIconsFiltered().
     *
     * @return array{sql: string, params: array<string, array{0: mixed, 1: int}>}
     */
    private static function buildFilterWhere(?string $search, ?bool $hasIcon): array
    {
        $clauses = [];
        $params  = [];

        if ($search !== null) {
            $clauses[]         = 'c.category_name_cat LIKE :search';
            $params[':search'] = ['%' . Database::escapeLike($search) . '%', PDO::PARAM_STR];
        }

        if ($hasIcon === true) {
            $clauses[] = 'c.id_vec_cat IS NOT NULL';
        } elseif ($hasIcon === false) {
            $clauses[] = 'c.id_vec_cat IS NULL';
        }

        return [
            'sql'    => $clauses !== [] ? 'WHERE ' . implode(' AND ', $clauses) : '',
            'params' => $params,
        ];
    }

    /**
     * Sortable, filterable category listing with icon data.
     *
     * @param  string  $sort    Validated column name
     * @param  string  $dir     ASC or DESC
     * @param  ?string $search  Category name substring
     * @param  ?bool   $hasIcon true = with icon, false = without icon, null = all
     * @return array
     */
    public static function getAllWithIconsFiltered(
        string $sort,
        string $dir,
        ?string $search,
        ?bool $hasIcon,
    ): array {
        $pdo = Database::connection();

        $sort = in_array($sort, self::ALLOWED_SORTS, true) ? $sort : self::DEFAULT_SORT;
        $dir  = strtoupper($dir);
        $dir  = ($dir === 'ASC' || $dir === 'DESC') ? $dir : self::DEFAULT_DIR;

        $filter = self::buildFilterWhere($search, $hasIcon);

        $sql = "
            SELECT c.id_cat,
                   c.category_name_cat,
                   c.id_vec_cat,
                   v.file_name_vec,
                   v.description_text_vec
            FROM category_cat c
            LEFT JOIN vector_image_vec v ON c.id_vec_cat = v.id_vec
            {$filter['sql']}
            ORDER BY {$sort} {$dir}
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($filter['params'] as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function updateIcon(int $categoryId, ?int $vectorId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE category_cat
            SET id_vec_cat = :vectorId
            WHERE id_cat = :categoryId
        ");

        $stmt->bindValue(':vectorId', $vectorId, $vectorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Fetch a single category by primary key.
     *
     * @param  int $id
     * @return ?array
     */
    public static function findById(int $id): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM category_cat WHERE id_cat = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Insert a new category.
     *
     * @param  string $name
     * @param  ?int   $vectorId
     * @return int    Inserted primary key
     */
    public static function create(string $name, ?int $vectorId): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            INSERT INTO category_cat (category_name_cat, id_vec_cat)
            VALUES (:name, :vectorId)
        ");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':vectorId', $vectorId, $vectorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update a category's name and icon.
     *
     * @param  int    $id
     * @param  string $name
     * @param  ?int   $vectorId
     * @return void
     */
    public static function update(int $id, string $name, ?int $vectorId): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            UPDATE category_cat
            SET category_name_cat = :name,
                id_vec_cat        = :vectorId
            WHERE id_cat = :id
        ");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':vectorId', $vectorId, $vectorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Delete a category by primary key.
     *
     * @param  int $id
     * @return void
     */
    public static function delete(int $id): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("DELETE FROM category_cat WHERE id_cat = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Remove tool junction rows then delete the category.
     *
     * @param  int $id
     * @return void
     */
    public static function forceDelete(int $id): void
    {
        $pdo = Database::connection();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("DELETE FROM tool_category_tolcat WHERE id_cat_tolcat = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM category_cat WHERE id_cat = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Count tools assigned to a category.
     *
     * @param  int $id
     * @return int
     */
    public static function getToolCount(int $id): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM tool_category_tolcat WHERE id_cat_tolcat = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
