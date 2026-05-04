<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * currencies.php OR currencies.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * currencies.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: currencies.php?action=list&success=created
 *
 * currencies.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: currencies.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: currencies.php?action=list&success=updated
 *
 * currencies.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: currencies.php?action=list&success=deleted or action=list&error=fk
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
$row = ['currency_id' => '', 'currency_name' => '', 'currency_code' => ''];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Currency created successfuly!',
    'updated' => 'Currency updated successfuly!',
    'deleted' => 'Currency deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ========================
 *  READ ALL
 * ========================
 */
if ($action === 'list') {
    $stmt = $pdo->query('SELECT * FROM currencies ORDER BY currency_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency_name = trim($_POST['currency_name'] ?? '');
    $currency_code = trim($_POST['currency_code'] ?? '');

    if ($currency_name === '') {
        $errors[] = 'Currency name is empty.';
    } elseif (mb_strlen($currency_name) > 25) {
        $errors[] = 'Currency name is over 25 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM currencies WHERE currency_name = ?');
        $stmt->execute([$currency_name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Currency name already exists.';
        }
    }

    if ($currency_code === '') {
        $errors[] = 'Currency code is empty.';
    } elseif (mb_strlen($currency_code) > 4) {
        $errors[] = 'Currency code longer than 4 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM currencies WHERE currency_code = ?');
        $stmt->execute([$currency_code]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Currency code already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO currencies (currency_name, currency_code) VALUES( :name , :code )');
            $stmt->execute([
                'name' => $currency_name,
                'code' => $currency_code,
            ]);
            header('Location: currencies.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Somethinng went wrong please try again!';
        }
    }

    $row['currency_name'] = $currency_name;
    $row['currency_code'] = $currency_code;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] == 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM currencies WHERE currency_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: currencies.php?action=list');
        exit;
    }
}

/**
 * ========================
 *  UPDATE (POST REQUEST)
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $currency_name = trim($_POST['currency_name'] ?? '');
    $currency_code = trim($_POST['currency_code'] ?? '');

    if ($currency_name === '') {
        $errors[] = 'Currency name is empty.';
    } elseif (mb_strlen($currency_name) > 25) {
        $errors[] = 'Currency name is over 25 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM currencies WHERE currency_name = :name AND currency_id != :id');
        $stmt->execute([
            'name' => $currency_name,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Currency name already exists.';
        }
    }

    if ($currency_code === '') {
        $errors[] = 'Currency code is empty.';
    } elseif (mb_strlen($currency_code) > 4) {
        $errors[] = 'Currency code longer than 4 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM currencies WHERE currency_code = :code AND currency_id != :id');
        $stmt->execute([
            'code' => $currency_code,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Currency code already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE currencies SET currency_name = :name , currency_code = :code WHERE currency_id = :id');
            $stmt->execute([
                'id' => $id,
                'name' => $currency_name,
                'code' => $currency_code,
            ]);
            header('Location: currencies.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['currency_id'] = $id;
    $row['currency_name'] = $currency_name;
    $row['currency_code'] = $currency_code;
}

/**
 * ========================
 *  DELETE (POST REQUEST)
 * ========================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM currencies WHERE currency_id = ?');
        $stmt->execute([$id]);
        header('Location: currencies.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: currencies.php?action=list&error=fk');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currencies</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Currencies Table</h1>
        
<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
           <div class="breadcrumb">
                <a href="currencies.php?action=list">Currencies</a>
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
                    <a href="currencies.php?action=create" class="btn btn-primary">+ Add New Currency</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No currencies found.</p>
                    <a href="currencies.php?action=create" class="btn btn-outline">Add the first one.</a>
                </div>
            <?php else: ?>  
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Currency Name</th>
                                <th>Currency Code</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="id-cell"><?= $record['currency_id']; ?></td>
                                <td><?= htmlspecialchars($record['currency_name']); ?></td>
                                <td><?= htmlspecialchars($record['currency_code']); ?></td>
                                <td class="actions-cell">
                                    <a href="currencies.php?action=edit&id=<?= $record['currency_id']; ?>" class="btn btn-outline">Edit</a>
                                    <form 
                                    action="currencies.php?action=delete&id=<?= $record['currency_id']; ?>" 
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
            <a href="currencies.php?action=list">Currencies</a>
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
            <h2>Add New Currency</h2>

            <form action="currencies.php?action=create" method="POST">
                <div class="form-group">
                    <label for="currency_name">Currency Name</label>
                    <input 
                    type="text" 
                    name="currency_name" 
                    value="<?= htmlspecialchars($row['currency_name']); ?>"
                    id="currency_name"
                    autocomplete="off"
                    maxlength="25"
                    placeholder="e.g Euro"
                    >
                    <p class="form-hint">Maximum 25 characters.</p>
                </div>

                <div class="form-group">
                    <label for="currency_code">Currency Code</label>
                    <input 
                    type="text" 
                    name="currency_code" 
                    value="<?= htmlspecialchars($row['currency_code']); ?>"
                    id="currency_code"
                    autocomplete="off"
                    maxlength="4"
                    placeholder="e.g EUR"
                    >
                    <p class="form-hint">Maximum 4 characters.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Currency</button>
                    <a href="currencies.php?action=list" class="btn btn-ghost">Cancel</a>
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
            <a href="currencies.php?action=list">Currencies</a>
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
            <h2>Edit Currency</h2>

            <form action="currencies.php?action=edit&id=<?= (int) $row['currency_id'] ?>" method="POST">
                <div class="form-group">
                    <label for="currency_name">Currency Name</label>
                    <input 
                    type="text" 
                    name="currency_name" 
                    value="<?= htmlspecialchars($row['currency_name']); ?>"
                    id="currency_name"
                    autocomplete="off"
                    maxlength="25"
                    placeholder="e.g Euro"
                    >
                    <p class="form-hint">Maximum 25 characters.</p>
                </div>

                <div class="form-group">
                    <label for="currency_code">Currency Code</label>
                    <input 
                    type="text" 
                    name="currency_code" 
                    value="<?= htmlspecialchars($row['currency_code']); ?>"
                    id="currency_code"
                    autocomplete="off"
                    maxlength="4"
                    placeholder="e.g EUR"
                    >
                    <p class="form-hint">Maximum 4 characters.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Currency</button>
                    <a href="currencies.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>