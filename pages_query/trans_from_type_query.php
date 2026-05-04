<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= Search Transactions from same Transaction Type =========
 *
 * required SQL table query fields:
 *  - select: options are from transaction_types table
 *
 * Form preserves transaction_type_id
 * server side validation error messages
 */

/**
 * ========================
 *  VARIABLES
 * ========================
 */
$records = [];
$errors = [];
$transaction_type_id = '';

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$transaction_types = [];
$stmt = $pdo->query('SELECT transaction_type_id, transaction_type_name FROM transaction_types ORDER BY transaction_type_name ASC');
$transaction_types = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_type_id = isset($_POST['transaction_type_id']) ? (int) $_POST['transaction_type_id'] : null;

    if ($transaction_type_id === null) {
        $errors[] = 'transaction type is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(' SELECT transactions.transaction_id,
        transactions.transaction_amount,
        transactions.transaction_datetime,
        transaction_types.transaction_type_name,
        clients.client_name,
        accounts.account_name,
        employees.employee_name
        FROM transactions
        LEFT JOIN transaction_types ON transactions.transaction_type_id = transaction_types.transaction_type_id   
        JOIN clients ON transactions.client_id = clients.client_id   
        LEFT JOIN accounts ON transactions.account_id = accounts.account_id   
        LEFT JOIN employees ON transactions.employee_id = employees.employee_id  
        WHERE transactions.transaction_type_id = :id
        ORDER BY transaction_types.transaction_type_name ASC');

        $stmt->execute([
            'id' => $transaction_type_id,
        ]);

        $records = $stmt->fetchAll();
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Bank Trnsactions From Type</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Search Bank Trnsactions From Type</h1>

        <div class="breadcrumb">
            <a href="/">home</a>
            <span>></span> Search Bank Trnsactions From Type
        </div>

         <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="form-card form-card--horizontal">
            <h2>Search Bank Transactions From Type</h2>
            
            <form action="trans_from_type_query.php" method="POST">
                
                <div class="form-group">
                    <label for="transaction_type_id">transaction type</label>
                    <select name="transaction_type_id" id="transaction_type_id">
                        <?php foreach ($transaction_types as $transaction_type): ?>
                            <option value="<?= $transaction_type['transaction_type_id'] ?>" 
                            <?= ($transaction_type_id == $transaction_type['transaction_type_id']) ? 'selected' : '' ?>>
                                <?= $transaction_type['transaction_type_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="/" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>

        <div class="spacer"></div>

        <?php if (empty($records)): ?>
            <div class="empty-state">
                <p>No transactions found.</p>
            </div>
        <?php else: ?>    
            <div class="table-wrapper">
                <h2><?= count($records); ?> Transactions Found</h2>
                <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>amount</th>
                                <th>datetime</th>
                                <th>type</th>
                                <th>from client</th>
                                <th>to account</th>
                                <th>by employee</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="id-cell"><?= $record['transaction_id']; ?></td>
                                <td><?= htmlspecialchars($record['transaction_amount']); ?></td>
                                <td><?= htmlspecialchars($record['transaction_datetime']); ?></td>
                                <td><?= htmlspecialchars($record['transaction_type_name']); ?></td>
                                <td><?= htmlspecialchars($record['client_name']); ?></td>
                                <td><?= htmlspecialchars($record['account_name']); ?></td>
                                <td><?= htmlspecialchars($record['employee_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
        <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>