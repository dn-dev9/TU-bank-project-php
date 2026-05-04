<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * employees.php OR employees.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * employees.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: employees.php?action=list&success=created
 *
 * employees.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: employees.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: employees.php?action=list&success=updated
 *
 * employees.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: employees.php?action=list&success=deleted or action=list&error=fk
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
    'employee_id' => '',
    'employee_name' => '',
    'employee_phone' => '',
    'role_id' => ''
];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Employee created successfuly!',
    'updated' => 'Employee updated successfuly!',
    'deleted' => 'Employee deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$roles = [];
$stmt = $pdo->query('SELECT role_id, role_name FROM roles ORDER BY role_name ASC');
$roles = $stmt->fetchAll();

/**
 * ========================
 *  READ ALL
 * ========================
 */
if ($action === 'list') {
    $stmt = $pdo->query(' SELECT employees.employee_id,
    employees.employee_name,
    employees.employee_phone,
    roles.role_name
    FROM employees
    LEFT JOIN roles ON employees.role_id = roles.role_id   
    ORDER BY employee_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = trim($_POST['employee_name'] ?? '');
    $employee_phone = trim($_POST['employee_phone'] ?? '');
    $role_id = isset($_POST['role_id']) ? (int) $_POST['role_id'] : null;

    if ($employee_name === '') {
        $errors[] = 'employee name is empty.';
    } elseif (mb_strlen($employee_name) > 35) {
        $errors[] = 'employee name is over 35 characters.';
    }

    if ($employee_phone === '') {
        $errors[] = 'employee phone is empty.';
    } elseif (mb_strlen($employee_phone) > 13) {
        $errors[] = 'employee phone is over 13 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE employee_phone = ?');
        $stmt->execute([$employee_phone]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'employee phone already exists.';
        }
    }

    if ($role_id === null) {
        $errors[] = 'role is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM roles WHERE role_id = :id');
        $stmt->execute([
            'id' => $role_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'role with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO employees (employee_name, employee_phone, role_id) VALUES (:name, :phone, :role_id)');
            $stmt->execute([
                'name' => $employee_name,
                'phone' => $employee_phone,
                'role_id' => $role_id,
            ]);
            header('Location: employees.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Somethinng went wrong please try again!';
        }
    }

    $row['employee_name'] = $employee_name;
    $row['employee_phone'] = $employee_phone;
    $row['role_id'] = $role_id;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] == 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE employee_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: employees.php?action=list');
        exit;
    }
}

/**
 * ========================
 *  UPDATE (POST REQUEST)
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $employee_name = trim($_POST['employee_name'] ?? '');
    $employee_phone = trim($_POST['employee_phone'] ?? '');
    $role_id = isset($_POST['role_id']) ? (int) $_POST['role_id'] : null;

    if ($employee_name === '') {
        $errors[] = 'employee name is empty.';
    } elseif (mb_strlen($employee_name) > 35) {
        $errors[] = 'employee name is over 35 characters.';
    }

    if ($employee_phone === '') {
        $errors[] = 'employee phone is empty.';
    } elseif (mb_strlen($employee_phone) > 13) {
        $errors[] = 'employee phone is over 13 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE employee_phone = :phone AND employee_id != :id');
        $stmt->execute([
            'phone' => $employee_phone,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'employee phone already exists.';
        }
    }

    if ($role_id === null) {
        $errors[] = 'role is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM roles WHERE role_id = :id');
        $stmt->execute([
            'id' => $role_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'role with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE employees SET 
            employee_name = :name , 
            employee_phone = :phone , 
            role_id = :role_id  
            WHERE employee_id = :employee_id');

            $stmt->execute([
                'employee_id' => $id,
                'name' => $employee_name,
                'phone' => $employee_phone,
                'role_id' => $role_id
            ]);
            header('Location: employees.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['employee_id'] = $id;
    $row['employee_name'] = $employee_name;
    $row['employee_phone'] = $employee_phone;
    $row['role_id'] = $role_id;
}

/**
 * ========================
 *  DELETE (POST REQUEST)
 * ========================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM employees WHERE employee_id = ?');
        $stmt->execute([$id]);
        header('Location: employees.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: employees.php?action=list&error=fk');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>employees</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>employees Table</h1>
        
<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
           <div class="breadcrumb">
                <a href="employees.php?action=list">employees</a>
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
                    <a href="employees.php?action=create" class="btn btn-primary">+ Add New employee</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No employees found.</p>
                    <a href="employees.php?action=create" class="btn btn-outline">Add the first one.</a>
                </div>
            <?php else: ?>      
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>role</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="id-cell"><?= $record['employee_id']; ?></td>
                                <td><?= htmlspecialchars($record['employee_name']); ?></td>
                                <td><?= htmlspecialchars($record['employee_phone']); ?></td>
                                <td><?= htmlspecialchars($record['role_name']); ?></td>
                                <td class="actions-cell">
                                    <a href="employees.php?action=edit&id=<?= $record['employee_id']; ?>" class="btn btn-outline">Edit</a>
                                    <form 
                                    action="employees.php?action=delete&id=<?= $record['employee_id']; ?>" 
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
            <a href="employees.php?action=list">employees</a>
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
            <h2>Add New employee</h2>

            <form action="employees.php?action=create" method="POST">

                <div class="form-group">
                    <label for="employee_name">employee Name</label>
                    <input 
                    type="text" 
                    name="employee_name" 
                    value="<?= htmlspecialchars($row['employee_name']); ?>"
                    id="employee_name"
                    autocomplete="off"
                    maxlength="35"
                    placeholder="Name"
                    >
                    <p class="form-hint">Maximum 35 characters.</p>
                </div>

                <div class="form-group">
                    <label for="employee_phone">employee phone</label>
                    <input 
                    type="tel" 
                    name="employee_phone" 
                    value="<?= htmlspecialchars($row['employee_phone']); ?>"
                    id="employee_phone"
                    autocomplete="off"
                    maxlength="13"
                    placeholder="phone"
                    >
                    <p class="form-hint">Maximum 13 characters.</p>
                </div>

                <div class="form-group">
                    <label for="role_id">employee role</label>
                    <select name="role_id" id="role_id">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['role_id'] ?>" 
                            <?= ($row['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                <?= $role['role_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save employee</button>
                    <a href="employees.php?action=list" class="btn btn-ghost">Cancel</a>
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
            <a href="employees.php?action=list">employees</a>
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
            <h2>Edit employee</h2>

            <form action="employees.php?action=edit&id=<?= (int) $row['employee_id'] ?>" method="POST">
                <div class="form-group">
                    <label for="employee_name">employee Name</label>
                    <input 
                    type="text" 
                    name="employee_name" 
                    value="<?= htmlspecialchars($row['employee_name']); ?>"
                    id="employee_name"
                    autocomplete="off"
                    maxlength="35"
                    placeholder="Name"
                    >
                    <p class="form-hint">Maximum 35 characters.</p>
                </div>

                <div class="form-group">
                    <label for="employee_phone">employee phone</label>
                    <input 
                    type="tel" 
                    name="employee_phone" 
                    value="<?= htmlspecialchars($row['employee_phone']); ?>"
                    id="employee_phone"
                    autocomplete="off"
                    maxlength="13"
                    placeholder="phone"
                    >
                    <p class="form-hint">Maximum 13 characters.</p>
                </div>

                <div class="form-group">
                    <label for="role_id">employee role</label>
                    <select name="role_id" id="role_id">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['role_id'] ?>" 
                            <?= ($row['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                <?= $role['role_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save employee</button>
                    <a href="employees.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>