<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * accounts.php OR accounts.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * accounts.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: accounts.php?action=list&success=created
 *
 * accounts.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: accounts.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: accounts.php?action=list&success=updated
 *
 * accounts.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: accounts.php?action=list&success=deleted or action=list&error=fk
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
    'account_id' => '',
    'account_name' => '',
    'account_interest_rate' => '',
    'account_balance' => '',
    'client_id' => '',
    'currency_id' => ''
];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Account created successfuly!',
    'updated' => 'Account updated successfuly!',
    'deleted' => 'Account deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$clients = [];
$stmt = $pdo->query('SELECT client_id, client_name FROM clients ORDER BY client_name ASC');
$clients = $stmt->fetchAll();

$currencies = [];
$stmt = $pdo->query('SELECT currency_id, currency_code FROM currencies ORDER BY currency_code ASC');
$currencies = $stmt->fetchAll();

/**
 * ========================
 *  READ ALL
 * ========================
 */
if ($action === 'list') {
    $stmt = $pdo->query(' SELECT accounts.account_id,
    accounts.account_name,
    accounts.account_interest_rate,
    accounts.account_balance,
    clients.client_name,
    currencies.currency_code
    FROM accounts
    LEFT JOIN clients ON accounts.client_id = clients.client_id   
    LEFT JOIN currencies ON accounts.currency_id = currencies.currency_id   
    ORDER BY account_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_name = trim($_POST['account_name'] ?? '');
    $account_interest_rate = trim($_POST['account_interest_rate'] ?? '');
    $account_balance = trim($_POST['account_balance'] ?? '');
    $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : null;
    $currency_id = isset($_POST['currency_id']) ? (int) $_POST['currency_id'] : null;

    if ($account_name === '') {
        $errors[] = 'account IBAN is empty.';
    } elseif (mb_strlen($account_name) > 22) {
        $errors[] = 'account IBAN is over 22 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE account_name = ?');
        $stmt->execute([$account_name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'account with this IBAN already exists.';
        }
    }

    if ($account_interest_rate === '') {
        $errors[] = 'Interest rate is required.';
    } elseif (!is_numeric($account_interest_rate)) {
        $errors[] = 'Interest rate must be a valid number.';
    } elseif ((float) $account_interest_rate < 0) {
        $errors[] = 'Interest rate cannot be negative.';
    }

    if ($account_balance === '') {
        $errors[] = 'Balance is required.';
    } elseif (!is_numeric($account_balance)) {
        $errors[] = 'Balance must be a valid number.';
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

    if ($currency_id === null) {
        $errors[] = 'Currency is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM currencies WHERE currency_id = :id');
        $stmt->execute([
            'id' => $currency_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'Currency with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO accounts (
                account_name,
                account_interest_rate,
                account_balance,
                client_id,
                currency_id
            ) VALUES (:name, :interest_rate, :balance, :client_id, :currency_id)');
            $stmt->execute([
                'name' => $account_name,
                'interest_rate' => $account_interest_rate,
                'balance' => $account_balance,
                'client_id' => $client_id,
                'currency_id' => $currency_id,
            ]);
            header('Location: accounts.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Somethinng went wrong please try again!';
        }
    }

    $row['account_name'] = $account_name;
    $row['account_interest_rate'] = $account_interest_rate;
    $row['account_balance'] = $account_balance;
    $row['client_id'] = $client_id;
    $row['currency_id'] = $currency_id;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] == 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE account_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: accounts.php?action=list');
        exit;
    }
}

/**
 * ========================
 *  UPDATE (POST REQUEST)
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $account_name = trim($_POST['account_name'] ?? '');
    $account_interest_rate = trim($_POST['account_interest_rate'] ?? '');
    $account_balance = trim($_POST['account_balance'] ?? '');
    $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : null;
    $currency_id = isset($_POST['currency_id']) ? (int) $_POST['currency_id'] : null;

    if ($account_name === '') {
        $errors[] = 'account IBAN is empty.';
    } elseif (mb_strlen($account_name) > 22) {
        $errors[] = 'account IBAN is over 22 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE account_name = :name AND account_id != :id');
        $stmt->execute([
            'name' => $account_name,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'account with this IBAN already exists.';
        }
    }

    if ($account_interest_rate === '') {
        $errors[] = 'Interest rate is required.';
    } elseif (!is_numeric($account_interest_rate)) {
        $errors[] = 'Interest rate must be a valid number.';
    } elseif ((float) $account_interest_rate < 0) {
        $errors[] = 'Interest rate cannot be negative.';
    }

    if ($account_balance === '') {
        $errors[] = 'Balance is required.';
    } elseif (!is_numeric($account_balance)) {
        $errors[] = 'Balance must be a valid number.';
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

    if ($currency_id === null) {
        $errors[] = 'Currency is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM currencies WHERE currency_id = :id');
        $stmt->execute([
            'id' => $currency_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'Currency with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE accounts SET 
            account_name = :name , 
            account_interest_rate = :interest_rate , 
            account_balance = :balance , 
            client_id = :client_id , 
            currency_id = :currency_id  
            WHERE account_id = :account_id');

            $stmt->execute([
                'account_id' => $id,
                'name' => $account_name,
                'interest_rate' => $account_interest_rate,
                'balance' => $account_balance,
                'client_id' => $client_id,
                'currency_id' => $currency_id,
            ]);

            header('Location: accounts.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['account_id'] = $id;
    $row['account_name'] = $account_name;
    $row['account_interest_rate'] = $account_interest_rate;
    $row['account_balance'] = $account_balance;
    $row['client_id'] = $client_id;
    $row['currency_id'] = $currency_id;
}

/**
 * ========================
 *  DELETE (POST REQUEST)
 * ========================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM accounts WHERE account_id = ?');
        $stmt->execute([$id]);
        header('Location: accounts.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: accounts.php?action=list&error=fk');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>accounts Table</h1>
        
<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
           <div class="breadcrumb">
                <a href="accounts.php?action=list">accounts</a>
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
                    <a href="accounts.php?action=create" class="btn btn-primary">+ Add New account</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No accounts found.</p>
                    <a href="accounts.php?action=create" class="btn btn-outline">Add the first one.</a>
                </div>
            <?php else: ?>    
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>interest rate</th>
                                <th>balance</th>
                                <th>client</th>
                                <th>currency</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="id-cell"><?= $record['account_id']; ?></td>
                                <td><?= htmlspecialchars($record['account_name']); ?></td>
                                <td><?= htmlspecialchars($record['account_interest_rate']); ?></td>
                                <td><?= htmlspecialchars($record['account_balance']); ?></td>
                                <td><?= htmlspecialchars($record['client_name']); ?></td>
                                <td><?= htmlspecialchars($record['currency_code']); ?></td>
                                <td class="actions-cell">
                                    <a href="accounts.php?action=edit&id=<?= $record['account_id']; ?>" class="btn btn-outline">Edit</a>
                                    <form 
                                    action="accounts.php?action=delete&id=<?= $record['account_id']; ?>" 
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
            <a href="accounts.php?action=list">accounts</a>
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
            <h2>Add New account</h2>

            <form action="accounts.php?action=create" method="POST">

                <div class="form-group">
                    <label for="account_name">account Name</label>
                    <input 
                    type="text" 
                    name="account_name" 
                    value="<?= htmlspecialchars($row['account_name']); ?>"
                    id="account_name"
                    autocomplete="off"
                    maxlength="22"
                    placeholder="e.g BG00BNBG00000000000000"

                    >
                    <p class="form-hint">Maximum 22 characters.</p>
                </div>

                <div class="form-group">
                    <label for="account_interest_rate">account interest rate</label>
                    <input 
                    type="text" 
                    name="account_interest_rate" 
                    value="<?= htmlspecialchars($row['account_interest_rate']); ?>"
                    id="account_interest_rate"
                    autocomplete="off"
                    maxlength="11"
                    placeholder="e.g. 9.9999"
                    >
                    <p class="form-hint">Max 10 digits with 4 digits precision after the decimal.</p>
                </div>

                <div class="form-group">
                    <label for="account_balance">account balance</label>
                    <input 
                    type="text" 
                    name="account_balance" 
                    value="<?= htmlspecialchars($row['account_balance']); ?>"
                    id="account_balance"
                    autocomplete="off"
                    maxlength="16"
                    placeholder="e.g. 9.99"
                    >
                    <p class="form-hint">Max 15 digits with 2 digits precision after the decimal. Negative balance is supported.</p>
                </div>

                <div class="form-group">
                    <label for="client_id">account client</label>
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
                    <label for="currency_id">account currency</label>
                    <select name="currency_id" id="currency_id">
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= $currency['currency_id'] ?>" 
                            <?= ($row['currency_id'] == $currency['currency_id']) ? 'selected' : '' ?>>
                                <?= $currency['currency_code'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save account</button>
                    <a href="accounts.php?action=list" class="btn btn-ghost">Cancel</a>
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
            <a href="accounts.php?action=list">accounts</a>
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
            <h2>Edit account</h2>

            <form action="accounts.php?action=edit&id=<?= (int) $row['account_id'] ?>" method="POST">

                  <div class="form-group">
                    <label for="account_name">account Name</label>
                    <input 
                    type="text" 
                    name="account_name" 
                    value="<?= htmlspecialchars($row['account_name']); ?>"
                    id="account_name"
                    autocomplete="off"
                    maxlength="22"
                    placeholder="Name"
                    >
                    <p class="form-hint">Maximum 22 characters.</p>
                </div>

                <div class="form-group">
                    <label for="account_interest_rate">account interest rate</label>
                    <input 
                    type="text" 
                    name="account_interest_rate" 
                    value="<?= htmlspecialchars($row['account_interest_rate']); ?>"
                    id="account_interest_rate"
                    autocomplete="off"
                    maxlength="11"
                    placeholder="e.g. 9.9999"
                    >
                    <p class="form-hint">Max 10 digits with 4 digits precision after the decimal.</p>
                </div>

                <div class="form-group">
                    <label for="account_balance">account balance</label>
                    <input 
                    type="text" 
                    name="account_balance" 
                    value="<?= htmlspecialchars($row['account_balance']); ?>"
                    id="account_balance"
                    autocomplete="off"
                    maxlength="16"
                    placeholder="e.g. 9.99"
                    >
                    <p class="form-hint">Max 15 digits with 2 digits precision after the decimal. Negative balance is supported.</p>
                </div>

                <div class="form-group">
                    <label for="client_id">account client</label>
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
                    <label for="currency_id">account currency</label>
                    <select name="currency_id" id="currency_id">
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= $currency['currency_id'] ?>" 
                            <?= ($row['currency_id'] == $currency['currency_id']) ? 'selected' : '' ?>>
                                <?= $currency['currency_code'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save account</button>
                    <a href="accounts.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>