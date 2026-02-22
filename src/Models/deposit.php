<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Deposit
{
    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM pending_deposit_v WHERE id_sdp = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public static function findByIdRaw(int $id): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id_sdp, id_bor_sdp, id_dps_sdp,
                    dps.status_name_dps AS deposit_status
             FROM security_deposit_sdp
             JOIN deposit_status_dps dps ON dps.id_dps = id_dps_sdp
             WHERE id_sdp = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public static function release(int $borrowId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_release_deposit_on_return(:borrow_id, @success, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    public static function forfeit(int $depositId, string $amount, string $reason, ?int $incidentId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_forfeit_deposit(:deposit_id, :amount, :reason, :incident_id, @success, @error_msg)'
        );

        $stmt->bindValue(':deposit_id',  $depositId, PDO::PARAM_INT);
        $stmt->bindValue(':amount',      $amount, PDO::PARAM_STR);
        $stmt->bindValue(':reason',      $reason, PDO::PARAM_STR);
        $stmt->bindValue(':incident_id', $incidentId, $incidentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    public static function getProviderIdByName(string $name): ?int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare('SELECT id_ppv FROM payment_provider_ppv WHERE provider_name_ppv = :name');
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? (int) $row['id_ppv'] : null;
    }

    /**
     * @return array{success: bool, id: ?int, error: ?string}
     */
    public static function createTransaction(
        ?int    $depositId,
        int     $borrowId,
        int     $providerId,
        string  $type,
        string  $amount,
        string  $externalId,
        ?string $externalStatus,
        ?int    $fromAccountId,
        ?int    $toAccountId,
    ): array {
        try {
            $pdo = Database::connection();

            $stmt = $pdo->prepare(
                'INSERT INTO payment_transaction_ptx
                    (id_sdp_ptx, id_bor_ptx, id_ppv_ptx, transaction_type_ptx,
                     amount_ptx, external_transaction_id_ptx, external_status_ptx,
                     id_acc_from_ptx, id_acc_to_ptx)
                 VALUES
                    (:deposit_id, :borrow_id, :provider_id, :type,
                     :amount, :external_id, :external_status,
                     :from_id, :to_id)'
            );

            $stmt->bindValue(':deposit_id',      $depositId, $depositId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':borrow_id',        $borrowId, PDO::PARAM_INT);
            $stmt->bindValue(':provider_id',      $providerId, PDO::PARAM_INT);
            $stmt->bindValue(':type',             $type, PDO::PARAM_STR);
            $stmt->bindValue(':amount',           $amount, PDO::PARAM_STR);
            $stmt->bindValue(':external_id',      $externalId, PDO::PARAM_STR);
            $stmt->bindValue(':external_status',  $externalStatus, $externalStatus === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':from_id',          $fromAccountId, $fromAccountId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':to_id',            $toAccountId, $toAccountId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->execute();

            return [
                'success' => true,
                'id'      => (int) $pdo->lastInsertId(),
                'error'   => null,
            ];
        } catch (\PDOException $e) {
            error_log('Deposit::createTransaction — ' . $e->getMessage());

            return [
                'success' => false,
                'id'      => null,
                'error'   => 'Failed to record transaction.',
            ];
        }
    }

    public static function createTransactionMeta(int $transactionId, string $key, string $value): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO payment_transaction_meta_ptm (id_ptx_ptm, meta_key_ptm, meta_value_ptm)
             VALUES (:tx_id, :key, :value)'
        );
        $stmt->bindValue(':tx_id', $transactionId, PDO::PARAM_INT);
        $stmt->bindValue(':key',   $key, PDO::PARAM_STR);
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function findPendingPayment(int $depositId): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT sdp.id_sdp, sdp.id_bor_sdp, sdp.amount_sdp,
                    sdp.external_payment_id_sdp,
                    bor.id_acc_bor        AS borrower_id,
                    tol.id_acc_tol        AS lender_id,
                    tol.tool_name_tol,
                    ppv.provider_name_ppv AS payment_provider
             FROM security_deposit_sdp sdp
             JOIN deposit_status_dps dps ON dps.id_dps = sdp.id_dps_sdp
             JOIN borrow_bor bor        ON bor.id_bor = sdp.id_bor_sdp
             JOIN tool_tol tol          ON tol.id_tol = bor.id_tol_bor
             JOIN payment_provider_ppv ppv ON ppv.id_ppv = sdp.id_ppv_sdp
             WHERE sdp.id_sdp = :id
               AND dps.status_name_dps = :status'
        );
        $stmt->bindValue(':id', $depositId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public static function storeExternalPaymentId(int $depositId, string $externalId): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE security_deposit_sdp
             SET external_payment_id_sdp = :external_id
             WHERE id_sdp = :id'
        );
        $stmt->bindValue(':external_id', $externalId, PDO::PARAM_STR);
        $stmt->bindValue(':id', $depositId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function getHistory(int $accountId, bool $isAdmin, int $limit, int $offset): array
    {
        $pdo = Database::connection();

        $sql = 'SELECT * FROM payment_history_v';

        if (!$isAdmin) {
            $sql .= ' WHERE id_acc_from_ptx = :acct_from OR id_acc_to_ptx = :acct_to';
        }

        $sql .= ' ORDER BY processed_at_ptx DESC LIMIT :lim OFFSET :off';

        $stmt = $pdo->prepare($sql);

        if (!$isAdmin) {
            $stmt->bindValue(':acct_from', $accountId, PDO::PARAM_INT);
            $stmt->bindValue(':acct_to',   $accountId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getHistoryCount(int $accountId, bool $isAdmin): int
    {
        $pdo = Database::connection();

        $sql = 'SELECT COUNT(*) FROM payment_history_v';

        if (!$isAdmin) {
            $sql .= ' WHERE id_acc_from_ptx = :acct_from OR id_acc_to_ptx = :acct_to';
        }

        $stmt = $pdo->prepare($sql);

        if (!$isAdmin) {
            $stmt->bindValue(':acct_from', $accountId, PDO::PARAM_INT);
            $stmt->bindValue(':acct_to',   $accountId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
