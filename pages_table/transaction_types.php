<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * transaction_types.php OR transaction_types.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * transaction_types.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: transaction_types.php?action=list&success=created
 *
 * transaction_types.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: transaction_types.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: transaction_types.php?action=list&success=updated
 *
 * transaction_types.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: transaction_types.php?action=list&success=deleted or action=list&error=fk
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
$row = ['transaction_type_id' => '', 'transaction_type_name' => ''];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Transaction Type created successfuly!',
    'updated' => 'Transaction Type updated successfuly!',
    'deleted' => 'Transaction Type deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ========================
 *  READ ALL
 * ========================
 */
if ($action === 'list') {
    $stmt = $pdo->query('SELECT * FROM transaction_types ORDER BY transaction_type_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_type_name = trim($_POST['transaction_type_name'] ?? '');

    if ($transaction_type_name === '') {
        $errors[] = 'Transaction Type name is empty.';
    } elseif (mb_strlen($transaction_type_name) > 15) {
        $errors[] = 'Transaction Type name is over 15 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaction_types WHERE transaction_type_name = ?');
        $stmt->execute([$transaction_type_name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Transaction Type name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO transaction_types (transaction_type_name) VALUES( :name )');
            $stmt->execute([
                'name' => $transaction_type_name,
            ]);
            header('Location: transaction_types.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong please try again!';
        }
    }

    $row['transaction_type_name'] = $transaction_type_name;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] == 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM transaction_types WHERE transaction_type_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: transaction_types.php?action=list');
        exit;
    }
}

/**
 * ========================
 *  UPDATE (POST REQUEST)
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $transaction_type_name = trim($_POST['transaction_type_name'] ?? '');

    if ($transaction_type_name === '') {
        $errors[] = 'Transaction Type name is empty.';
    } elseif (mb_strlen($transaction_type_name) > 15) {
        $errors[] = 'Transaction Type name is over 15 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaction_types WHERE transaction_type_name = :name AND transaction_type_id != :id');
        $stmt->execute([
            'name' => $transaction_type_name,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Transaction Type name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE transaction_types SET transaction_type_name = :name WHERE transaction_type_id = :id');
            $stmt->execute([
                'id' => $id,
                'name' => $transaction_type_name,
            ]);
            header('Location: transaction_types.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['transaction_type_id'] = $id;
    $row['transaction_type_name'] = $transaction_type_name;
}

/**
 * ========================
 *  DELETE (POST REQUEST)
 * ========================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM transaction_types WHERE transaction_type_id = ?');
        $stmt->execute([$id]);
        header('Location: transaction_types.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: transaction_types.php?action=list&error=fk');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Type</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Transaction Types Table</h1>
        
<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
           <div class="breadcrumb">
                <a href="transaction_types.php?action=list">Transaction Types</a>
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
                    <a href="transaction_types.php?action=create" class="btn btn-primary">+ Add New Transaction Type</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No Transaction Types found.</p>
                    <a href="transaction_types.php?action=create" class="btn btn-outline">Add the first one.</a>
                </div>
            <?php else: ?>                    
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Transaction Type Name</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="id-cell"><?= $record['transaction_type_id']; ?></td>
                                <td><?= htmlspecialchars($record['transaction_type_name']); ?></td>
                                <td class="actions-cell">
                                    <a href="transaction_types.php?action=edit&id=<?= $record['transaction_type_id']; ?>" class="btn btn-outline">Edit</a>
                                    <form 
                                    action="transaction_types.php?action=delete&id=<?= $record['transaction_type_id']; ?>" 
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
            <a href="transaction_types.php?action=list">Transaction Types</a>
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
            <h2>Add New Transaction Type</h2>

            <form action="transaction_types.php?action=create" method="POST">
                <div class="form-group">
                    <label for="transaction_type_name">Transaction Type Name</label>
                    <input 
                    type="text" 
                    name="transaction_type_name" 
                    value="<?= htmlspecialchars($row['transaction_type_name']); ?>"
                    id="transaction_type_name"
                    autocomplete="off"
                    maxlength="15"
                    placeholder="e.g Euro"
                    >
                    <p class="form-hint">Maximum 15 characters.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Transaction Type</button>
                    <a href="transaction_types.php?action=list" class="btn btn-ghost">Cancel</a>
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
            <a href="transaction_types.php?action=list">Transaction Types</a>
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
            <h2>Edit Transaction Type</h2>

            <form action="transaction_types.php?action=edit&id=<?= (int) $row['transaction_type_id'] ?>" method="POST">
                <div class="form-group">
                    <label for="transaction_type_name">Transaction Type Name</label>
                    <input 
                    type="text" 
                    name="transaction_type_name" 
                    value="<?= htmlspecialchars($row['transaction_type_name']); ?>"
                    id="transaction_type_name"
                    autocomplete="off"
                    maxlength="15"
                    placeholder="e.g Euro"
                    >
                    <p class="form-hint">Maximum 15 characters.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Transaction Type</button>
                    <a href="transaction_types.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>