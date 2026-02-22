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

    public static function getCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query("
            SELECT COUNT(*) FROM vector_image_vec
        ")->fetchColumn();
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
}
