<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * roles.php OR roles.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * roles.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: roles.php?action=list&success=created
 *
 * roles.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: roles.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: roles.php?action=list&success=updated
 *
 * roles.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: roles.php?action=list&success=deleted or action=list&error=fk
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
$row = ['role_id' => '', 'role_name' => ''];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Role created successfuly!',
    'updated' => 'Role updated successfuly!',
    'deleted' => 'Role deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ========================
 *  READ ALL
 * ========================
 */
if ($action === 'list') {
    $stmt = $pdo->query('SELECT * FROM roles ORDER BY role_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['role_name'] ?? '');

    if ($role_name === '') {
        $errors[] = 'Role name is empty.';
    } elseif (mb_strlen($role_name) > 20) {
        $errors[] = 'Role name is over 20 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE role_name = ?');
        $stmt->execute([$role_name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Role name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO roles (role_name) VALUES( :name )');
            $stmt->execute([
                'name' => $role_name,
            ]);
            header('Location: roles.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Somethinng went wrong please try again!';
        }
    }

    $row['role_name'] = $role_name;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] == 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM roles WHERE role_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: roles.php?action=list');
        exit;
    }
}

/**
 * ========================
 *  UPDATE (POST REQUEST)
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $role_name = trim($_POST['role_name'] ?? '');

    if ($role_name === '') {
        $errors[] = 'Role name is empty.';
    } elseif (mb_strlen($role_name) > 20) {
        $errors[] = 'Role name is over 20 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE role_name = :name AND role_id != :id');
        $stmt->execute([
            'name' => $role_name,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Role name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE roles SET role_name = :name WHERE role_id = :id');
            $stmt->execute([
                'id' => $id,
                'name' => $role_name,
            ]);
            header('Location: roles.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['role_id'] = $id;
    $row['role_name'] = $role_name;
}

/**
 * ========================
 *  DELETE (POST REQUEST)
 * ========================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM roles WHERE role_id = ?');
        $stmt->execute([$id]);
        header('Location: roles.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: roles.php?action=list&error=fk');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Roles Table</h1>
        
<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
           <div class="breadcrumb">
                <a href="roles.php?action=list">Roles</a>
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
                    <a href="roles.php?action=create" class="btn btn-primary">+ Add New Role</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No roles found.</p>
                    <a href="roles.php?action=create" class="btn btn-outline">Add the first one.</a>
                </div>
            <?php else: ?>                    
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Role Name</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="id-cell"><?= $record['role_id']; ?></td>
                                <td><?= htmlspecialchars($record['role_name']); ?></td>
                                <td class="actions-cell">
                                    <a href="roles.php?action=edit&id=<?= $record['role_id']; ?>" class="btn btn-outline">Edit</a>
                                    <form 
                                    action="roles.php?action=delete&id=<?= $record['role_id']; ?>" 
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
            <a href="roles.php?action=list">Roles</a>
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
            <h2>Add New Role</h2>

            <form action="roles.php?action=create" method="POST">
                <div class="form-group">
                    <label for="role_name">Role Name</label>
                    <input 
                    type="text" 
                    name="role_name" 
                    value="<?= htmlspecialchars($row['role_name']); ?>"
                    id="role_name"
                    autocomplete="off"
                    maxlength="20"
                    placeholder="e.g Euro"
                    >
                    <p class="form-hint">Maximum 20 characters.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Role</button>
                    <a href="roles.php?action=list" class="btn btn-ghost">Cancel</a>
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
            <a href="roles.php?action=list">Roles</a>
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
            <h2>Edit Role</h2>

            <form action="roles.php?action=edit&id=<?= (int) $row['role_id'] ?>" method="POST">
                <div class="form-group">
                    <label for="role_name">Role Name</label>
                    <input 
                    type="text" 
                    name="role_name" 
                    value="<?= htmlspecialchars($row['role_name']); ?>"
                    id="role_name"
                    autocomplete="off"
                    maxlength="20"
                    placeholder="e.g Euro"
                    >
                    <p class="form-hint">Maximum 20 characters.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Role</button>
                    <a href="roles.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>