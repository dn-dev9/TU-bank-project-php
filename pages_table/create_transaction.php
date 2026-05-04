<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * create_transaction.php? + 'success=created'
 * GET REQUEST
 *  - Shows Transaction Create Form
 *    FIELDS:
 *      - Transaction Amount
 *      - transaction type is always 'transfer'
 *      - Sender Account IBAN (find the client who owns this iban, send their ID to the DB )
 *      - recipient Account IBAN (find account ID of IBAN)
 *      - infers employee_id needed in transactions table from the current Logged In employee in Session
 *  - displays messages from previous actions
 * POST REQUEST
 * - Handle form input
 */

/**
 * ========================
 *  VARIABLES
 * ========================
 */
$errors = [];
$employee_id = $_SESSION['employee_id'];
$transaction_type_id = null;
$transaction_amount = '';
$sender_account_name = '';
$recipient_account_name = '';

$flashSuccess = ($_GET['success'] ?? '') == 'created' ? 'Transaction was succcessful.' : '';

/**
 * ========================
 *  Joined Tables
 * ========================
 */
$stmt = $pdo->query("SELECT transaction_type_id FROM transaction_types WHERE transaction_type_name = 'Transfer'");
$transaction_type_id = $stmt->fetch();

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_type_id = isset($transaction_type_id) ? (int) $transaction_type_id : null;
    $employee_id = isset($employee_id) ? (int) $employee_id : null;
    $transaction_amount = trim($_POST['transaction_amount'] ?? '');
    $sender_account_name = trim($_POST['sender_account_name'] ?? '');
    $recipient_account_name = trim($_POST['recipient_account_name'] ?? '');
    $client_id = null;
    $sender_account_id = null;
    $recipient_account_id = null;  // same as account_id

    if ($transaction_type_id === null) {
        $errors[] = 'transaction type "Transfer" does not exist. Please add it to Transaction Types Table to make a transaction';
    }

    if ($employee_id === null) {
        $errors[] = 'Log in as an employee to make a transaction.';
    }

    if ($transaction_amount === '') {
        $errors[] = 'transaction amount is required.';
    } elseif (!is_numeric($transaction_amount)) {
        $errors[] = 'transaction amount must be a valid number.';
    } elseif ((float) $transaction_amount < 0) {
        $errors[] = 'transaction amount cannot be negative.';
    } elseif ((float) $transaction_amount < 1) {
        $errors[] = 'minimal transaction amount is 1$.';
    }

    if ($sender_account_name === '') {
        $errors[] = 'Sender account IBAN is empty.';
    } elseif (mb_strlen($sender_account_name) > 22) {
        $errors[] = 'Sender account IBAN is over 22 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT account_id, client_id  FROM accounts WHERE account_name = ?');
        $stmt->execute([$sender_account_name]);
        $sender_record = $stmt->fetch();
        if (empty($sender_record)) {
            $errors[] = 'Sender account with this IBAN does not exist.';
        } else {
            $client_id = $sender_record['client_id'];
            $sender_account_id = $sender_record['account_id'];
        }
    }

    if ($recipient_account_name === '') {
        $errors[] = 'recipient account IBAN is empty.';
    } elseif (mb_strlen($recipient_account_name) > 22) {
        $errors[] = 'recipient account IBAN is over 22 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT account_id FROM accounts WHERE account_name = ?');
        $stmt->execute([$recipient_account_name]);
        $recipient_record = $stmt->fetch();
        if (empty($recipient_record)) {
            $errors[] = 'recipient account with this IBAN does not exist.';
        } else {
            $recipient_account_id = $recipient_record['account_id'];
        }
    }

    if ($sender_account_name === $recipient_account_name) {
        $errors[] = 'Sender and Recpient are the same account.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT account_balance FROM accounts WHERE account_id = :id FOR UPDATE');
            $stmt->execute(['id' => $sender_account_id]);
            $sender = $stmt->fetch();

            if (!$sender)
                throw new Exception('Sender account not found');

            if ($sender['account_balance'] < floatval($transaction_amount)) {
                $errors[] = 'Sender account has insufficient funds.';
                throw new Exception('insufficient funds');
            }

            $stmt = $pdo->prepare('UPDATE accounts SET account_balance = account_balance - :amount WHERE account_id = :sender_id');
            $stmt->execute([
                'amount' => $transaction_amount,
                'sender_id' => $sender_account_id
            ]);
            $sender = $stmt->fetch();

            $stmt = $pdo->prepare('UPDATE accounts SET account_balance = account_balance + :amount WHERE account_id = :recipient_id');
            $stmt->execute([
                'amount' => $transaction_amount,
                'recipient_id' => $recipient_account_id
            ]);

            $stmt = $pdo->prepare('INSERT INTO transactions (
                transaction_amount,
                transaction_type_id,
                client_id,
                account_id,
                employee_id
            ) VALUES (:amount, :type_id, :client_id, :account_id, :employee_id)');

            $stmt->execute([
                'amount' => $transaction_amount,
                'type_id' => $transaction_type_id,
                'client_id' => $client_id,
                'account_id' => $recipient_account_id,
                'employee_id' => $employee_id
            ]);

            $pdo->commit();
            header('Location: create_transaction.php?success=created');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Transaction</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Make Transaction</h1>

        <div class="breadcrumb">
            <a href="/">Home</a>
            <span>></span> Make Transaction
        </div>

        <?php if ($flashSuccess): ?>
            <div class="flash flash-success"><?= htmlspecialchars($flashSuccess); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="form-card <?= !empty($errors) ? 'form-error' : ''; ?>">
            <h2>Make a transaction</h2>

            <form action="create_transaction.php" method="POST">

                <div class="form-group">
                    <label for="transaction_amount">transaction amount</label>
                    <input 
                    type="text" 
                    name="transaction_amount" 
                    value="<?= htmlspecialchars($transaction_amount); ?>"
                    id="transaction_amount"
                    autocomplete="off"
                    maxlength="16"
                    placeholder="e.g. 9.9999"
                    >
                    <p class="form-hint">Max 15 digits with 2 digits precision after the decimal.</p>
                </div>

                <div class="form-group">
                    <label for="sender_account_name">Sender account Name</label>
                    <input 
                    type="text" 
                    name="sender_account_name" 
                    value="<?= htmlspecialchars($sender_account_name); ?>"
                    id="sender_account_name"
                    autocomplete="off"
                    maxlength="22"
                    placeholder="e.g BG00BNBG00000000000000"

                    >
                    <p class="form-hint">Maximum 22 characters.</p>
                </div>

                <div class="form-group">
                    <label for="recipient_account_name">Recipient account Name</label>
                    <input 
                    type="text" 
                    name="recipient_account_name" 
                    value="<?= htmlspecialchars($recipient_account_name); ?>"
                    id="recipient_account_name"
                    autocomplete="off"
                    maxlength="22"
                    placeholder="e.g BG00BNBG00000000000000"

                    >
                    <p class="form-hint">Maximum 22 characters.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Make transaction</button>
                    <a href="/" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>