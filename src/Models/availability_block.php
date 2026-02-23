<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AvailabilityBlock
{
    /**
     * Fetch all availability blocks for a tool, newest start date first.
     */
    public static function getForTool(int $toolId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                avb.id_avb,
                avb.id_tol_avb,
                btp.type_name_btp AS block_type,
                avb.start_at_avb,
                avb.end_at_avb,
                avb.notes_text_avb,
                avb.created_at_avb,
                b.id_bor AS borrow_id,
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS borrower_name
            FROM availability_block_avb avb
            JOIN block_type_btp btp ON avb.id_btp_avb = btp.id_btp
            LEFT JOIN borrow_bor b ON avb.id_bor_avb = b.id_bor
            LEFT JOIN account_acc a ON b.id_acc_bor = a.id_acc
            WHERE avb.id_tol_avb = :tool_id
            ORDER BY avb.start_at_avb DESC
        ");

        $stmt->bindValue(':tool_id', $toolId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch a single availability block by ID.
     */
    public static function findById(int $blockId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                avb.id_avb,
                avb.id_tol_avb,
                btp.type_name_btp AS block_type,
                avb.start_at_avb,
                avb.end_at_avb,
                avb.notes_text_avb
            FROM availability_block_avb avb
            JOIN block_type_btp btp ON avb.id_btp_avb = btp.id_btp
            WHERE avb.id_avb = :block_id
        ");

        $stmt->bindValue(':block_id', $blockId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Create an admin-type availability block.
     *
     * @return int The new block ID
     */
    public static function create(int $toolId, string $startAt, string $endAt, ?string $notes): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            INSERT INTO availability_block_avb (
                id_tol_avb,
                id_btp_avb,
                start_at_avb,
                end_at_avb,
                notes_text_avb
            ) VALUES (
                :tool_id,
                fn_get_block_type_id('admin'),
                :start_at,
                :end_at,
                :notes
            )
        ");

        $stmt->bindValue(':tool_id', $toolId, PDO::PARAM_INT);
        $stmt->bindValue(':start_at', $startAt, PDO::PARAM_STR);
        $stmt->bindValue(':end_at', $endAt, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, $notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /**
     * Delete an availability block by ID.
     */
    public static function delete(int $blockId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            DELETE FROM availability_block_avb
            WHERE id_avb = :block_id
        ");

        $stmt->bindValue(':block_id', $blockId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
