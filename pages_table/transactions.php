<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * transactions.php OR transactions.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * transactions.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: transactions.php?action=list&success=created
 *
 * transactions.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: transactions.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: transactions.php?action=list&success=updated
 *
 * transactions.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: transactions.php?action=list&success=deleted or action=list&error=fk
 */

/**
 * ========================
 *  VARIABLES
 * ========================
 */
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$errors = [];
$records = [];
$row = [
    'transaction_id' => '',
    'transaction_amount' => '',
    'transaction_datetime' => '',
    'transaction_type_id' => '',
    'client_id' => '',
    'account_id' => '',
    'employee_id' => '',
];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Transaction created successfuly!',
    'updated' => 'Transaction updated successfuly!',
    'deleted' => 'Transaction deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$transaction_types = [];
$stmt = $pdo->query('SELECT transaction_type_id, transaction_type_name FROM transaction_types ORDER BY transaction_type_name ASC');
$transaction_types = $stmt->fetchAll();

$clients = [];
$stmt = $pdo->query('SELECT client_id, client_name FROM clients ORDER BY client_name ASC');
$clients = $stmt->fetchAll();

$accounts = [];
$stmt = $pdo->query('SELECT account_id, account_name FROM accounts ORDER BY account_name ASC');
$accounts = $stmt->fetchAll();

$employees = [];
$stmt = $pdo->query('SELECT employee_id, employee_name FROM employees ORDER BY employee_name ASC');
$employees = $stmt->fetchAll();

/**
 * ========================
 *  READ ALL
 * ========================
 */
if ($action === 'list') {
    $stmt = $pdo->query(' SELECT transactions.transaction_id,
    transactions.transaction_amount,
    transactions.transaction_datetime,
    transaction_types.transaction_type_name,
    clients.client_name,
    accounts.account_name,
    employees.employee_name
    FROM transactions
    LEFT JOIN transaction_types ON transactions.transaction_type_id = transaction_types.transaction_type_id   
    LEFT JOIN clients ON transactions.client_id = clients.client_id   
    LEFT JOIN accounts ON transactions.account_id = accounts.account_id   
    LEFT JOIN employees ON transactions.employee_id = employees.employee_id   
    ORDER BY transaction_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_amount = trim($_POST['transaction_amount'] ?? '');
    $transaction_type_id = isset($_POST['transaction_type_id']) ? (int) $_POST['transaction_type_id'] : null;
    $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : null;
    $account_id = isset($_POST['account_id']) ? (int) $_POST['account_id'] : null;
    $employee_id = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : null;

    if ($transaction_amount === '') {
        $errors[] = 'transaction amount is required.';
    } elseif (!is_numeric($transaction_amount)) {
        $errors[] = 'transaction amount must be a valid number.';
    } elseif ((float) $transaction_amount < 0) {
        $errors[] = 'transaction amount cannot be negative.';
    } elseif ((float) $transaction_amount < 1) {
        $errors[] = 'minimal transaction amount is 1$.';
    }

    if ($transaction_type_id === null) {
        $errors[] = 'transaction type is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM transaction_types WHERE transaction_type_id = :id');
        $stmt->execute([
            'id' => $transaction_type_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'transaction type with this ID does not exist.';
        }
    }

    if ($client_id === null) {
        $errors[] = 'Client is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM clients WHERE client_id = :id');
        $stmt->execute([
            'id' => $client_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'Client with this ID does not exist.';
        }
    }

    if ($account_id === null) {
        $errors[] = 'account is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM accounts WHERE account_id = :id');
        $stmt->execute([
            'id' => $account_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'account with this ID does not exist.';
        }
    }

    if ($employee_id === null) {
        $errors[] = 'employee is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM employees WHERE employee_id = :id');
        $stmt->execute([
            'id' => $employee_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'employee with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
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
                'account_id' => $account_id,
                'employee_id' => $employee_id
            ]);
            header('Location: transactions.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Somethinng went wrong please try again!';
        }
    }

    $row['transaction_amount'] = $transaction_amount;
    $row['transaction_type_id'] = $transaction_type_id;
    $row['client_id'] = $client_id;
    $row['account_id'] = $account_id;
    $row['employee_id'] = $employee_id;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] == 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE transaction_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: transactions.php?action=list');
        exit;
    }
}

/**
 * ========================
 *  UPDATE (POST REQUEST)
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $transaction_amount = trim($_POST['transaction_amount'] ?? '');
    $transaction_type_id = isset($_POST['transaction_type_id']) ? (int) $_POST['transaction_type_id'] : null;
    $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : null;
    $account_id = isset($_POST['account_id']) ? (int) $_POST['account_id'] : null;
    $employee_id = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : null;

    if ($transaction_amount === '') {
        $errors[] = 'transaction amount is required.';
    } elseif (!is_numeric($transaction_amount)) {
        $errors[] = 'transaction amount must be a valid number.';
    } elseif ((float) $transaction_amount < 0) {
        $errors[] = 'transaction amount cannot be negative.';
    } elseif ((float) $transaction_amount < 1) {
        $errors[] = 'minimal transaction amount is 1$.';
    }

    if ($transaction_type_id === null) {
        $errors[] = 'transaction type is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM transaction_types WHERE transaction_type_id = :id');
        $stmt->execute([
            'id' => $transaction_type_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'transaction type with this ID does not exist.';
        }
    }

    if ($client_id === null) {
        $errors[] = 'Client is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM clients WHERE client_id = :id');
        $stmt->execute([
            'id' => $client_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'Client with this ID does not exist.';
        }
    }

    if ($account_id === null) {
        $errors[] = 'account is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM accounts WHERE account_id = :id');
        $stmt->execute([
            'id' => $account_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'account with this ID does not exist.';
        }
    }

    if ($employee_id === null) {
        $errors[] = 'employee is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM employees WHERE employee_id = :id');
        $stmt->execute([
            'id' => $employee_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'employee with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE transactions SET 
            transaction_amount = :amount , 
            transaction_type_id = :type_id , 
            client_id = :client_id,
            account_id = :account_id,
            employee_id = :employee_id
            WHERE transaction_id = :transaction_id');

            $stmt->execute([
                'transaction_id' => $id,
                'amount' => $transaction_amount,
                'type_id' => $transaction_type_id,
                'client_id' => $client_id,
                'account_id' => $account_id,
                'employee_id' => $employee_id
            ]);

            header('Location: transactions.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['transaction_id'] = $id;
    $row['transaction_amount'] = $transaction_amount;
    $row['transaction_type_id'] = $transaction_type_id;
    $row['client_id'] = $client_id;
    $row['account_id'] = $account_id;
    $row['employee_id'] = $employee_id;
}

/**
 * ========================
 *  DELETE (POST REQUEST)
 * ========================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE transaction_id = ?');
        $stmt->execute([$id]);
        header('Location: transactions.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: transactions.php?action=list&error=fk');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>transactions</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>transactions Table</h1>
        
<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
           <div class="breadcrumb">
                <a href="transactions.php?action=list">transactions</a>
            </div>

           <?php if ($flashSuccess): ?>
                <div class="flash flash-success"><?= htmlspecialchars($flashSuccess); ?></div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="flash flash-error"><?= htmlspecialchars($flashError); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-toolbar">
                    <span class="record-count"><?= count($records); ?> records</span>
                    <a href="transactions.php?action=create" class="btn btn-primary">+ Add New transaction</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No transactions found.</p>
                    <a href="transactions.php?action=create" class="btn btn-outline">Add the first one.</a>
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
                                <th>by employee</th>
                                <th></th>
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
                                <td class="actions-cell">
                                    <a href="transactions.php?action=edit&id=<?= $record['transaction_id']; ?>" class="btn btn-outline">Edit</a>
                                    <form 
                                    action="transactions.php?action=delete&id=<?= $record['transaction_id']; ?>" 
                                    method="post"
                                    class="delete-form"
                                    onsubmit="return confirm('Confirm Delete of record?');"
                                    >
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>     
            <?php endif; ?>
        <?php endif; ?>

<?php

/**
 * ============================
 *  CREATE
 * ============================
 */
?>
    <?php if ($action === 'create'): ?>

        <div class="breadcrumb">
            <a href="transactions.php?action=list">transactions</a>
            <span>></span> Add New
        </div>

        <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="form-card <?= !empty($errors) ? 'form-error' : ''; ?>">
            <h2>Make a transaction</h2>

            <form action="transactions.php?action=create" method="POST">

                <div class="form-group">
                    <label for="transaction_amount">transaction amount</label>
                    <input 
                    type="text" 
                    name="transaction_amount" 
                    value="<?= htmlspecialchars($row['transaction_amount']); ?>"
                    id="transaction_amount"
                    autocomplete="off"
                    maxlength="16"
                    placeholder="e.g. 9.9999"
                    >
                    <p class="form-hint">Max 15 digits with 2 digits precision after the decimal.</p>
                </div>

                <div class="form-group">
                    <label for="transaction_type_id">transaction type</label>
                    <select name="transaction_type_id" id="transaction_type_id">
                        <?php foreach ($transaction_types as $transaction_type): ?>
                            <option value="<?= $transaction_type['transaction_type_id'] ?>" 
                            <?= ($row['transaction_type_id'] == $transaction_type['transaction_type_id']) ? 'selected' : '' ?>>
                                <?= $transaction_type['transaction_type_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="client_id">from client</label>
                    <select name="client_id" id="client_id">
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['client_id'] ?>" 
                            <?= ($row['client_id'] == $client['client_id']) ? 'selected' : '' ?>>
                                <?= $client['client_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="account_id">send to account</label>
                    <select name="account_id" id="account_id">
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['account_id'] ?>" 
                            <?= ($row['account_id'] == $account['account_id']) ? 'selected' : '' ?>>
                                <?= $account['account_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="employee_id">Handled By employee</label>
                    <select name="employee_id" id="employee_id">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= $employee['employee_id'] ?>" 
                            <?= ($row['employee_id'] == $employee['employee_id']) ? 'selected' : '' ?>>
                                <?= $employee['employee_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save transaction</button>
                    <a href="transactions.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>

    <?php endif; ?>

<?php

/**
 * ============================
 *  EDIT
 * ============================
 */
?>
    <?php if ($action === 'edit' && $row): ?>
        <div class="breadcrumb">
            <a href="transactions.php?action=list">transactions</a>
            <span>></span> Edit
        </div>

        <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="form-card <?= !empty($errors) ? 'form-error' : ''; ?>">
            <h2>Edit transaction</h2>

            <form action="transactions.php?action=edit&id=<?= (int) $row['transaction_id'] ?>" method="POST">

                  <div class="form-group">
                    <label for="transaction_amount">transaction amount</label>
                    <input 
                    type="text" 
                    name="transaction_amount" 
                    value="<?= htmlspecialchars($row['transaction_amount']); ?>"
                    id="transaction_amount"
                    autocomplete="off"
                    maxlength="16"
                    placeholder="e.g. 9.9999"
                    >
                    <p class="form-hint">Max 15 digits with 2 digits precision after the decimal.</p>
                </div>

                <div class="form-group">
                    <label for="transaction_type_id">transaction type</label>
                    <select name="transaction_type_id" id="transaction_type_id">
                        <?php foreach ($transaction_types as $transaction_type): ?>
                            <option value="<?= $transaction_type['transaction_type_id'] ?>" 
                            <?= ($row['transaction_type_id'] == $transaction_type['transaction_type_id']) ? 'selected' : '' ?>>
                                <?= $transaction_type['transaction_type_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="client_id">from client</label>
                    <select name="client_id" id="client_id">
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['client_id'] ?>" 
                            <?= ($row['client_id'] == $client['client_id']) ? 'selected' : '' ?>>
                                <?= $client['client_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="account_id">send to account</label>
                    <select name="account_id" id="account_id">
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['account_id'] ?>" 
                            <?= ($row['account_id'] == $account['account_id']) ? 'selected' : '' ?>>
                                <?= $account['account_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="employee_id">Handled By employee</label>
                    <select name="employee_id" id="employee_id">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= $employee['employee_id'] ?>" 
                            <?= ($row['employee_id'] == $employee['employee_id']) ? 'selected' : '' ?>>
                                <?= $employee['employee_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save transaction</button>
                    <a href="transactions.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>