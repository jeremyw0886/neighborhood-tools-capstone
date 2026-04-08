<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class VectorImage
{
    public static function getAll(): array
    {
        $pdo = Database::connection();

        return $pdo->query("
            SELECT v.*,
                   a.first_name_acc, a.last_name_acc,
                   c.category_name_cat AS assigned_category
            FROM vector_image_vec v
            JOIN account_acc a ON v.id_acc_vec = a.id_acc
            LEFT JOIN category_cat c ON c.id_vec_cat = v.id_vec
            ORDER BY v.uploaded_at_vec DESC
        ")->fetchAll();
    }

    /** @return array Paginated rows with uploader name and assigned category. */
    public static function getPaged(int $limit, int $offset): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT v.*,
                   a.first_name_acc, a.last_name_acc,
                   c.category_name_cat AS assigned_category
            FROM vector_image_vec v
            JOIN account_acc a ON v.id_acc_vec = a.id_acc
            LEFT JOIN category_cat c ON c.id_vec_cat = v.id_vec
            ORDER BY v.uploaded_at_vec DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array Category icons matching the search term by filename, description, or assigned category. */
    public static function adminSearch(string $term, int $limit = 5): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                v.id_vec,
                v.file_name_vec,
                v.description_text_vec,
                c.category_name_cat AS assigned_category
            FROM vector_image_vec v
            LEFT JOIN category_cat c ON c.id_vec_cat = v.id_vec
            WHERE v.file_name_vec LIKE CONCAT('%', :term1, '%')
               OR v.description_text_vec LIKE CONCAT('%', :term2, '%')
               OR c.category_name_cat LIKE CONCAT('%', :term3, '%')
            ORDER BY v.file_name_vec ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':term1', $term);
        $stmt->bindValue(':term2', $term);
        $stmt->bindValue(':term3', $term);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query("
            SELECT COUNT(*) FROM vector_image_vec
        ")->fetchColumn();
    }

    /**
     * Count category icons matching optional search and assignment filter.
     *
     * @param  ?string $search    Filename or description substring
     * @param  ?bool   $assigned  true = assigned only, false = unassigned only, null = all
     * @return int
     */
    public static function getFilteredCount(?string $search, ?bool $assigned): int
    {
        $pdo = Database::connection();

        $filter = self::buildFilterWhere($search, $assigned);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM vector_image_vec v LEFT JOIN category_cat c ON c.id_vec_cat = v.id_vec "
            . $filter['sql']
        );

        foreach ($filter['params'] as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Build the shared WHERE clause used by getFilteredCount() and getFiltered().
     *
     * @return array{sql: string, params: array<string, array{0: mixed, 1: int}>}
     */
    private static function buildFilterWhere(?string $search, ?bool $assigned): array
    {
        $clauses = [];
        $params  = [];

        if ($search !== null) {
            $clauses[]          = '(v.file_name_vec LIKE :search1 OR v.description_text_vec LIKE :search2 OR c.category_name_cat LIKE :search3)';
            $params[':search1'] = ['%' . $search . '%', PDO::PARAM_STR];
            $params[':search2'] = ['%' . $search . '%', PDO::PARAM_STR];
            $params[':search3'] = ['%' . $search . '%', PDO::PARAM_STR];
        }

        if ($assigned === true) {
            $clauses[] = 'c.id_cat IS NOT NULL';
        } elseif ($assigned === false) {
            $clauses[] = 'c.id_cat IS NULL';
        }

        return [
            'sql'    => $clauses !== [] ? 'WHERE ' . implode(' AND ', $clauses) : '',
            'params' => $params,
        ];
    }

    /**
     * Paginated, sortable, filterable category icon listing.
     *
     * @param  int     $limit
     * @param  int     $offset
     * @param  string  $sort      Validated column name
     * @param  string  $dir       ASC or DESC
     * @param  ?string $search    Filename or description substring
     * @param  ?bool   $assigned  true = assigned only, false = unassigned only, null = all
     * @return array
     */
    public static function getFiltered(
        int $limit,
        int $offset,
        string $sort,
        string $dir,
        ?string $search,
        ?bool $assigned,
    ): array {
        $pdo = Database::connection();

        $filter = self::buildFilterWhere($search, $assigned);

        $sql = "
            SELECT v.*,
                   a.first_name_acc, a.last_name_acc,
                   c.category_name_cat AS assigned_category
            FROM vector_image_vec v
            JOIN account_acc a ON v.id_acc_vec = a.id_acc
            LEFT JOIN category_cat c ON c.id_vec_cat = v.id_vec
            {$filter['sql']}
            ORDER BY {$sort} {$dir}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($filter['params'] as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT * FROM vector_image_vec WHERE id_vec = :id
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public static function create(string $fileName, ?string $description, int $uploaderId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            INSERT INTO vector_image_vec (file_name_vec, description_text_vec, id_acc_vec)
            VALUES (:fileName, :description, :uploaderId)
        ");

        $stmt->bindValue(':fileName', $fileName);
        $stmt->bindValue(':description', $description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':uploaderId', $uploaderId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /** Update description text. */
    public static function updateDescription(int $id, ?string $description): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE vector_image_vec
            SET description_text_vec = :description
            WHERE id_vec = :id
        ");

        $stmt->bindValue(':description', $description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM category_cat WHERE id_vec_cat = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $pdo->prepare("
            DELETE FROM vector_image_vec WHERE id_vec = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }

    /**
     * Unassign from all categories then delete the record.
     *
     * @param  int $id
     * @return void
     */
    public static function forceDelete(int $id): void
    {
        $pdo = Database::connection();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE category_cat SET id_vec_cat = NULL WHERE id_vec_cat = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM vector_image_vec WHERE id_vec = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
