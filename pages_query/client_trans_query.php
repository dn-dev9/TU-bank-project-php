<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= Search Transactions of Client for Period order by Date and Type =========
 *
 * required SQL table query fields:
 *  - client (select)
 *  - from date input:datetime-local fields
 *  - to date
 *
 * Form preserves client id, and dates after POST Request
 * server side validation error messages
 * JS Validation of dates, From Date to be before To Date
 */

/**
 * ========================
 *  VARIABLES
 * ========================
 */
$records = [];
$errors = [];
$client_id = '';
$from_date = '';
$to_date = '';

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$clients = [];
$stmt = $pdo->query('SELECT client_id, client_name FROM clients ORDER BY client_name ASC');
$clients = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : null;
    $from_date = (!empty($_POST['from_date'])) ? str_replace('T', ' ', $_POST['from_date']) . ':00' : '';
    $to_date = (!empty($_POST['to_date'])) ? str_replace('T', ' ', $_POST['to_date']) . ':00' : '';

    if ($from_date === '') {
        $errors[] = 'From Date is required';
    }

    if ($to_date === '') {
        $errors[] = 'To Date is required';
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
        WHERE transactions.client_id = :id
        AND transactions.transaction_datetime BETWEEN :from_date AND :to_date 
        ORDER BY transactions.transaction_datetime ASC');

        $stmt->execute([
            'id' => $client_id,
            'from_date' => $from_date,
            'to_date' => $to_date
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
    <title>Search Client Trnsactions</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Search Client Transactions</h1>

        <div class="breadcrumb">
            <a href="/">home</a>
            <span>></span> Search Client Transactions
        </div>

         <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="form-card form-card--horizontal">
            <h2>Search Client Transactions for Period</h2>
            
            <form action="client_trans_query.php" method="POST">
                
                <div class="form-group">
                    <label for="client_id">client</label>
                    <select name="client_id" id="client_id">
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['client_id'] ?>" 
                            <?= ($client_id == $client['client_id']) ? 'selected' : '' ?>>
                            <?= $client['client_name'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="from_date">from date</label>
                    <input 
                    type="datetime-local" 
                    name="from_date" 
                    value="<?= htmlspecialchars($from_date); ?>"
                    id="from_date"
                    min="1000-01-01T00:00"
                    max="9999-12-31T23:59"
                >
                </div>

                <div class="form-group">
                    <label for="to_date">to date</label>
                    <input 
                    type="datetime-local" 
                    name="to_date" 
                    value="<?= htmlspecialchars($to_date); ?>"
                    id="to_date"
                    min="1000-01-01T00:00"
                    max="9999-12-31T23:59"
                >
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