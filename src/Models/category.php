<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Category
{
    public static function getAll(): array
    {
        $pdo = Database::connection();

        return $pdo->query("
            SELECT *
            FROM category_summary_v
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
}
