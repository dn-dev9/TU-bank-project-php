<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * addresses.php OR addresses.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * addresses.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: addresses.php?action=list&success=created
 *
 * addresses.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: currencies.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: addresses.php?action=list&success=updated
 *
 * addresses.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: addresses.php?action=list&success=deleted or action=list&error=fk
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
$row = ['address_id' => '', 'address_name' => ''];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Address created successfuly!',
    'updated' => 'Address updated successfuly!',
    'deleted' => 'Address deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ============================
 *  READ ALL
 * ============================
 */
if ($action == 'list') {
    $stmt = $pdo->query('SELECT * FROM addresses ORDER BY address_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ============================
 *  CREATE (POST REQUEST)
 * ============================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_name = trim($_POST['address_name'] ?? '');

    if ($address_name === '') {
        $errors[] = 'Address name is required!';
    } else if (mb_strlen($address_name) > 15) {
        $errors[] = 'Address name must be max 15 characters!';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE address_name = ?');
        $stmt->execute([$address_name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Address name must be unique';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO addresses (address_name) VALUES (?)');
            $stmt->execute([$address_name]);
            header('Location:  addresses.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Somethinng went wrong please try again!';
        }
    }

    $row['address_name'] = $address_name;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM addresses WHERE address_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: addresses.php?action=list');
        exit;
    }
}

/**
 * ============================
 *  UPDATE (POST REQUEST)
 * ============================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $address_name = trim($_POST['address_name'] ?? '');

    if ($address_name == '') {
        $errors[] = 'Address Name must not be empty!';
    } else if (mb_strlen($address_name) > 15) {
        $errors[] = 'Address Name must not be more than 15 characters!';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE address_name = ? AND address_id != ?');
        $stmt->execute([$address_name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Address Name must be unique!';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE addresses SET address_name = ? WHERE address_id = ?');
            $stmt->execute([$address_name, $id]);
            header('Location: addresses.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['address_id'] = $id;
    $row['address_name'] = $address_name;
}

/**
 * ============================
 *  DELETE (POST REQUEST)
 * ============================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM addresses WHERE address_id = ?');
        $stmt->execute([$id]);
        header('Location: addresses.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: addresses.php?action=list&error=fk');
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Addresses</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Addresses Table</h1>


<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
            <div class="breadcrumb">
                <a href="addresses.php?action=list">Addresses</a>
            </div>

            <?php if ($flashSuccess): ?>
            <div class="flash flash-success"><?= htmlspecialchars($flashSuccess); ?></div>
            <?php endif; ?>

            <?php if ($flashError): ?>
            <div class="flash flash-error"><?= htmlspecialchars($flashError); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-toolbar">
                    <span class="record-count"><?= count($records) ?> records</span>
                    <a href="addresses.php?action=create" class="btn btn-primary">+ Add New Address</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No addresses Found.</p>
                    <a href="addresses.php?action=create" class="btn btn-outline">Add the first one.</a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Address Name</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $addr): ?>
                                <tr>
                                    <td class="id-cell"><?= $addr['address_id']; ?></td>
                                    <td><?= htmlspecialchars($addr['address_name']); ?></td>
                                    <td class="actions-cell">
                                        <a href="addresses.php?action=edit&id=<?= $addr['address_id'] ?>" class="btn btn-outline">Edit</a>
                                        <form 
                                        class="delete-form"
                                        method="POST" 
                                        action="addresses.php?action=delete&id=<?= $addr['address_id'] ?>" 
                                        onsubmit="return confirm('Do you really want to delete this record?');"
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
                <a href="addresses.php?action=list">Addresses</a>
                <span>></span> Add New
            </div>

            <?php if (!empty($errors)): ?>
                <ul class="error-list">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="form-card <?= !empty($errors) ? 'form-error' : ''; ?>">
                <h2>Add New Address</h2>

                <form method="POST" action="addresses.php?action=create">
                    <div class="form-group">
                        <label for="address_name">Address Name</label>
                        <input 
                        type="text" 
                        name="address_name" 
                        id="address_name"
                        value="<?= htmlspecialchars($row['address_name']) ?>"
                        maxlength="15"
                        autocomplete="off"                        
                        placeholder="e.g Sofia"
                        />
                        <p class="form-hint">Maximum 15 characters. Must be unique!</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Address</button>
                        <a href="addresses.php?action=list" class="btn btn-ghost">Cancel</a>
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
                <a href="addresses.php?action=list">Addresses</a>
                <span>></span> Edit
            </div>

            <?php if (!empty($errors)): ?>
                <ul class="error-list">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="form-card <?= !empty($errors) ? 'form-error' : ''; ?>">
                <h2>Edit Address</h2>

                <form method="POST" action="addresses.php?action=edit&id=<?= (int) $row['address_id'] ?>">
                    <div class="form-group">
                        <label for="address_name">Address Name</label>
                        <input 
                        type="text" 
                        name="address_name" 
                        id="address_name"
                        value="<?= htmlspecialchars($row['address_name']) ?>"
                        maxlength="15"
                        autocomplete="off"
                        placeholder="e.g Sofia"
                        />
                        <p class="form-hint">Maximum 15 characters. Must be unique!</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Address</button>
                        <a href="addresses.php?action=list" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>