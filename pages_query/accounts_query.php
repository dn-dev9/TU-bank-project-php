<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= Search accounts by: Client, Account Number, Client EGN =========
 *
 * SQL table query fields:
 *  - client_id (Select),
 *  - account_name(IBAN text),
 *  - client_egn(egn text)
 *
 * Tabs with JS behaviour show different query forms
 * Tab Index is preserved on page reload after POST request
 */

/**
 * ========================
 *  VARIABLES
 * ========================
 */
$search_method = trim($_POST['search_method'] ?? '');
$tab_index = isset($_GET['tab_index']) ? (int) $_GET['tab_index'] : 1;
$records = [];
$errors = [];
$client_id = '';
$account_name = '';
$client_egn = '';

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$clients = [];
$stmt = $pdo->query('SELECT client_id, client_name FROM clients ORDER BY client_name ASC');
$clients = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $search_method === 'client_id') {
    $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : null;

    if ($client_id === null) {
        $errors[] = 'Cliend is required.';
    }

    if (empty($error)) {
        $stmt = $pdo->prepare(' SELECT accounts.account_id,
        accounts.account_name,
        accounts.account_interest_rate,
        accounts.account_balance,
        clients.client_name,
        currencies.currency_code
        FROM accounts
        LEFT JOIN clients ON accounts.client_id = clients.client_id   
        LEFT JOIN currencies ON accounts.currency_id = currencies.currency_id   
        WHERE accounts.client_id = ?
        ORDER BY account_id ASC');
        $stmt->execute([$client_id]);
        $records = $stmt->fetchAll();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $search_method === 'account_name') {
    $account_name = trim($_POST['account_name'] ?? '');

    if ($account_name === '') {
        $errors[] = 'Account Name is required.';
    }

    if (empty($error)) {
        $stmt = $pdo->prepare(' SELECT accounts.account_id,
        accounts.account_name,
        accounts.account_interest_rate,
        accounts.account_balance,
        clients.client_name,
        currencies.currency_code
        FROM accounts
        LEFT JOIN clients ON accounts.client_id = clients.client_id   
        LEFT JOIN currencies ON accounts.currency_id = currencies.currency_id   
        WHERE accounts.account_name = ?
        ORDER BY account_id ASC');
        $stmt->execute([$account_name]);
        $records = $stmt->fetchAll();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $search_method === 'client_egn') {
    $client_egn = trim($_POST['client_egn'] ?? '');

    if ($client_egn === '') {
        $errors[] = 'Client EGN is required.';
    }

    if (empty($error)) {
        $stmt = $pdo->prepare(' SELECT accounts.account_id,
        accounts.account_name,
        accounts.account_interest_rate,
        accounts.account_balance,
        clients.client_name,
        currencies.currency_code
        FROM accounts
        LEFT JOIN clients ON accounts.client_id = clients.client_id   
        LEFT JOIN currencies ON accounts.currency_id = currencies.currency_id   
        WHERE clients.client_egn = ?
        ORDER BY account_id ASC');
        $stmt->execute([$client_egn]);
        $records = $stmt->fetchAll();
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Accounts</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Search for Account</h1>

        <div class="breadcrumb">
            <a href="/">home</a>
            <span>></span> Search for Account
        </div>

        <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="tabs-card tabs">
            <div class="tabs-card-tabs tab-buttons-container">
                <h3>Search By</h3>
                <button class="tab-btn btn btn-outline <?= $tab_index == 1 ? 'active' : '' ?>" tab-index="1">
                    Client
                </button>
                <button class="tab-btn btn btn-outline <?= $tab_index == 2 ? 'active' : '' ?>" tab-index="2">
                    Account Name
                </button>
                <button class="tab-btn btn btn-outline <?= $tab_index == 3 ? 'active' : '' ?>" tab-index="3">
                    Client EGN
                </button>
            </div>
            <div class="tabs-card-content">
                <div class="tab-pane <?= $tab_index == 1 ? 'active' : '' ?>" tab-index="1">
                    <div class="form-card ">
                        <h2>Search By Client</h2>
                        
                        <form action="accounts_query.php?tab_index=1" method="POST">
                            
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
                            
                            <input type="hidden" name="search_method" value="client_id">

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="/" class="btn btn-ghost">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane <?= $tab_index == 2 ? 'active' : '' ?>" tab-index="2">
                    <div class="form-card ">
                        <h2>Search By Account Name</h2>
                        
                        <form action="accounts_query.php?tab_index=2" method="POST">
                            
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

                            <input type="hidden" name="search_method" value="account_name">

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="/" class="btn btn-ghost">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane <?= $tab_index == 3 ? 'active' : '' ?>" tab-index="3">
                    <div class="form-card ">
                        <h2>Search By Client EGN</h2>
                        
                        <form action="accounts_query.php?tab_index=3" method="POST">
                            
                            <div class="form-group">
                                <label for="client_egn">client EGN</label>
                                <input 
                                type="text" 
                                name="client_egn" 
                                value="<?= htmlspecialchars($client_egn); ?>"
                                id="client_egn"
                                autocomplete="off"
                                maxlength="10"
                                placeholder="e.g. 89********"
                            >
                            <p class="form-hint">Maximum 10 characters.</p>
                            </div>
                            
                            <input type="hidden" name="search_method" value="client_egn">
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="/" class="btn btn-ghost">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>


            </div>
        </div>

        <?php if (empty($records)): ?>
            <div class="empty-state">
                <p>No accounts found.</p>
            </div>
        <?php else: ?>    
            <div class="table-wrapper">
                <h2>Accounts Found</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>interest rate</th>
                            <th>balance</th>
                            <th>client</th>
                            <th>currency</th>
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