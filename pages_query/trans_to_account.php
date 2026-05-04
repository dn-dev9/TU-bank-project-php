<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= Search Transactions to an Account =========
 *
 * required SQL table query fields:
 *  - text field IBAN string
 *
 * Form preserves account_name
 * server side validation error messages
 */

/**
 * ========================
 *  VARIABLES
 * ========================
 */
$records = [];
$errors = [];
$account_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_name = trim($_POST['account_name'] ?? '');

    if ($account_name === '') {
        $errors[] = 'Account Name is required.';
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
        LEFT JOIN clients ON transactions.client_id = clients.client_id   
        JOIN accounts ON transactions.account_id = accounts.account_id   
        LEFT JOIN employees ON transactions.employee_id = employees.employee_id  
        WHERE accounts.account_name = :account_name
        ORDER BY transaction_types.transaction_type_name ASC');

        $stmt->execute([
            'account_name' => $account_name,
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
    <title>Search Trnsactions To Account</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Search Trnsactions To Account</h1>

        <div class="breadcrumb">
            <a href="/">home</a>
            <span>></span> Search Trnsactions To Account
        </div>

         <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="form-card form-card--horizontal">
            <h2>Trnsactions To Account</h2>
            
            <form action="trans_to_account.php" method="POST">
                
               <div class="form-group">
                                <label for="account_name">account Number</label>
                                <input 
                                type="text" 
                                name="account_name" 
                                value="<?= htmlspecialchars($account_name); ?>"
                                id="account_name"
                                autocomplete="off"
                                maxlength="22"
                                placeholder="e.g BG00BNBG00000000000000"
                            >
                    <p class="form-hint">Maximum 22 characters.</p>
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