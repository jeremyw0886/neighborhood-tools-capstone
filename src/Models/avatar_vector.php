<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AvatarVector
{
    /** All rows with uploader name and user count (admin listing). */
    public static function getAll(): array
    {
        $pdo = Database::connection();

        return $pdo->query("
            SELECT v.*,
                   a.first_name_acc, a.last_name_acc,
                   (SELECT COUNT(*) FROM account_acc
                    WHERE id_avv_acc = v.id_avv) AS user_count
            FROM avatar_vector_avv v
            JOIN account_acc a ON v.id_acc_avv = a.id_acc
            ORDER BY v.uploaded_at_avv DESC
        ")->fetchAll();
    }

    /** @return array Paginated rows with uploader name and user count. */
    public static function getPaged(int $limit, int $offset): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT v.*,
                   a.first_name_acc, a.last_name_acc,
                   (SELECT COUNT(*) FROM account_acc
                    WHERE id_avv_acc = v.id_avv) AS user_count
            FROM avatar_vector_avv v
            JOIN account_acc a ON v.id_acc_avv = a.id_acc
            ORDER BY v.uploaded_at_avv DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Only active vectors (user picker). */
    public static function getActive(): array
    {
        $pdo = Database::connection();

        return $pdo->query("
            SELECT id_avv, file_name_avv, description_text_avv
            FROM avatar_vector_avv
            WHERE is_active_avv = TRUE
            ORDER BY uploaded_at_avv DESC
        ")->fetchAll();
    }

    /** @return int Total avatar vector count. */
    public static function getCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query("
            SELECT COUNT(*) FROM avatar_vector_avv
        ")->fetchColumn();
    }

    /**
     * Count avatar vectors matching optional search and active-status filter.
     *
     * @param  ?string $search Filename or description substring
     * @param  ?bool   $active true = active only, false = inactive only, null = all
     * @return int
     */
    public static function getFilteredCount(?string $search, ?bool $active): int
    {
        $pdo = Database::connection();

        $sql    = "SELECT COUNT(*) FROM avatar_vector_avv v WHERE 1=1";
        $params = [];

        if ($search !== null) {
            $sql .= " AND (v.file_name_avv LIKE :search1 OR v.description_text_avv LIKE :search2)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($active === true) {
            $sql .= " AND v.is_active_avv = TRUE";
        } elseif ($active === false) {
            $sql .= " AND v.is_active_avv = FALSE";
        }

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated, sortable, filterable avatar vector listing.
     *
     * @param  int     $limit
     * @param  int     $offset
     * @param  string  $sort   Validated column name
     * @param  string  $dir    ASC or DESC
     * @param  ?string $search Filename or description substring
     * @param  ?bool   $active true = active only, false = inactive only, null = all
     * @return array
     */
    public static function getFiltered(
        int $limit,
        int $offset,
        string $sort,
        string $dir,
        ?string $search,
        ?bool $active,
    ): array {
        $pdo = Database::connection();

        $sql = "
            SELECT v.*,
                   a.first_name_acc, a.last_name_acc,
                   (SELECT COUNT(*) FROM account_acc
                    WHERE id_avv_acc = v.id_avv) AS user_count
            FROM avatar_vector_avv v
            JOIN account_acc a ON v.id_acc_avv = a.id_acc
            WHERE 1=1
        ";
        $params = [];

        if ($search !== null) {
            $sql .= " AND (v.file_name_avv LIKE :search1 OR v.description_text_avv LIKE :search2)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($active === true) {
            $sql .= " AND v.is_active_avv = TRUE";
        } elseif ($active === false) {
            $sql .= " AND v.is_active_avv = FALSE";
        }

        $sql .= " ORDER BY {$sort} {$dir} LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return ?array Single row or null. */
    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT * FROM avatar_vector_avv WHERE id_avv = :id
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return int Inserted row ID. */
    public static function create(string $fileName, ?string $description, int $uploaderId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            INSERT INTO avatar_vector_avv (file_name_avv, description_text_avv, id_acc_avv)
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
            UPDATE avatar_vector_avv
            SET description_text_avv = :description
            WHERE id_avv = :id
        ");

        $stmt->bindValue(':description', $description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** Flip is_active_avv. */
    public static function toggleActive(int $id): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE avatar_vector_avv
            SET is_active_avv = NOT is_active_avv
            WHERE id_avv = :id
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** Delete if no users reference it; returns false when blocked. */
    public static function delete(int $id): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM account_acc WHERE id_avv_acc = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $pdo->prepare("
            DELETE FROM avatar_vector_avv WHERE id_avv = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }

    /**
     * Unassign from all users then delete the record.
     *
     * @param  int $id
     * @return void
     */
    public static function forceDelete(int $id): void
    {
        $pdo = Database::connection();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE account_acc SET id_avv_acc = NULL WHERE id_avv_acc = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM avatar_vector_avv WHERE id_avv = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
