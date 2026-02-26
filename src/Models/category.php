<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Category
{
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

        $sql    = "SELECT COUNT(*) FROM category_cat c LEFT JOIN vector_image_vec v ON c.id_vec_cat = v.id_vec WHERE 1=1";
        $params = [];

        if ($search !== null) {
            $sql .= " AND c.category_name_cat LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        if ($hasIcon === true) {
            $sql .= " AND c.id_vec_cat IS NOT NULL";
        } elseif ($hasIcon === false) {
            $sql .= " AND c.id_vec_cat IS NULL";
        }

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
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

        $sql = "
            SELECT c.id_cat,
                   c.category_name_cat,
                   c.id_vec_cat,
                   v.file_name_vec,
                   v.description_text_vec
            FROM category_cat c
            LEFT JOIN vector_image_vec v ON c.id_vec_cat = v.id_vec
            WHERE 1=1
        ";
        $params = [];

        if ($search !== null) {
            $sql .= " AND c.category_name_cat LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        if ($hasIcon === true) {
            $sql .= " AND c.id_vec_cat IS NOT NULL";
        } elseif ($hasIcon === false) {
            $sql .= " AND c.id_vec_cat IS NULL";
        }

        $sql .= " ORDER BY {$sort} {$dir}";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
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
