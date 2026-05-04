<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= Search top 5 highest amount transactions of each employee =========
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

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$employees = [];
$stmt = $pdo->query('SELECT employee_id, employee_name FROM employees ORDER BY employee_name ASC');
$employees = $stmt->fetchAll();

$stmt = $pdo->query(
    ' SELECT * FROM (
    SELECT 
        transactions.transaction_id,
        transactions.transaction_amount,
        transactions.transaction_datetime,
        transaction_types.transaction_type_name,
        clients.client_name,
        accounts.account_name,
        employees.employee_id,
        employees.employee_name,
        ROW_NUMBER() OVER (
            PARTITION BY transactions.employee_id 
            ORDER BY transactions.transaction_amount DESC
        ) AS row_num
    FROM transactions
    LEFT JOIN transaction_types ON transactions.transaction_type_id = transaction_types.transaction_type_id
    LEFT JOIN clients ON transactions.client_id = clients.client_id
    LEFT JOIN accounts ON transactions.account_id = accounts.account_id
    JOIN employees ON transactions.employee_id = employees.employee_id
) AS T
WHERE T.row_num <= 5
ORDER BY T.employee_name ASC, T.transaction_amount DESC'
);

$records = $stmt->fetchAll();

$employee_records_map = [];

foreach ($records as $record) {
    $employee_records_map[$record['employee_id']][] = $record;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List top 5 transactions</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>List of top 5 highest amount transactions per employee</h1>

        <div class="breadcrumb">
            <a href="/">home</a>
            <span>></span> List of top 5 highest amount transactions per employee
        </div>

         <div class="employees-ranking-container">
            <?php foreach ($employees as $employee): ?>
                <h2><?= htmlspecialchars($employee['employee_name']); ?></h2>

                <?php if (!isset($employee_records_map[$employee['employee_id']])): ?>
                    <div class="empty-state">
                        <p>No transactions found.</p>
                    </div>
                <?php else: ?>   
                    <div class="table-wrapper">
                        <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>amount</th>
                                        <th>datetime</th>
                                        <th>type</th>
                                        <th>from client</th>
                                        <th>to account</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employee_records_map[$employee['employee_id']] as $transaction): ?>
                                    <tr>
                                        <td class="id-cell"><?= $transaction['transaction_id']; ?></td>
                                        <td><?= htmlspecialchars($transaction['transaction_amount']); ?></td>
                                        <td><?= htmlspecialchars($transaction['transaction_datetime']); ?></td>
                                        <td><?= htmlspecialchars($transaction['transaction_type_name']); ?></td>
                                        <td><?= htmlspecialchars($transaction['client_name']); ?></td>
                                        <td><?= htmlspecialchars($transaction['account_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>