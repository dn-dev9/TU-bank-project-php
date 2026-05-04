<?php
require_once '../helpers.php';
require_once basePath('includes/db.php');
requireLogin();

/**
 * ========= SUPPORTED URLS =========
 * clients.php OR clients.php?action=list + '&success=created/updated/deleted' / '&error=fk'
 * GET request
 *  - List all records
 *  - display messages from previous actions
 *
 * clients.php?action=create
 * GET request
 *  - (IN HTML) display form with input fields for each column
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: clients.php?action=list&success=created
 *
 * clients.php?action=edit&id=?
 * GET REQUEST
 *  - PHP reads record with ID from DB -> (IN HTML) prefills the form input fields
 *  - if no record found route to: clients.php?action=list
 * POST REQUEST
 *  - sanitize input, check for correctness, check if unique -> save record
 *  - if errors -> display them, prefill input fields after page reload
 *  - route to: clients.php?action=list&success=updated
 *
 * clients.php?action=delete&id=?
 * POST REQUEST -> initiated from DELETE button in table view
 *  - show prompt for confirmation -> proceed with deletion
 *  - php listens for POST request, action=delete&id=?
 *  - deletes record from DB
 *  - route to: clients.php?action=list&success=deleted or action=list&error=fk
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
    'client_id' => '',
    'client_name' => '',
    'client_egn' => '',
    'client_phone' => '',
    'address_id' => ''
];

$flashSuccess = match ($_GET['success'] ?? '') {
    'created' => 'Client created successfuly!',
    'updated' => 'Client updated successfuly!',
    'deleted' => 'Client deleted successfuly!',
    default => ''
};

$flashError = ($_GET['error'] ?? '') == 'fk' ? 'Record is a Foreign Key in another table' : '';

/**
 * ========================
 *  Joined Tables FK & Value Lists
 * ========================
 */
$addresses = [];
$stmt = $pdo->query('SELECT address_id, address_name FROM addresses ORDER BY address_name ASC');
$addresses = $stmt->fetchAll();

/**
 * ========================
 *  READ ALL
 * ========================
 */
if ($action === 'list') {
    $stmt = $pdo->query(' SELECT clients.client_id,
    clients.client_name,
    clients.client_egn,
    clients.client_phone,
    addresses.address_name
    FROM clients
    LEFT JOIN addresses ON clients.address_id = addresses.address_id   
    ORDER BY client_id ASC');
    $records = $stmt->fetchAll();
}

/**
 * ========================
 *  CREATE (POST REQUEST)
 * ========================
 */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_egn = trim($_POST['client_egn'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    $address_id = isset($_POST['address_id']) ? (int) $_POST['address_id'] : null;

    if ($client_name === '') {
        $errors[] = 'Client name is empty.';
    } elseif (mb_strlen($client_name) > 35) {
        $errors[] = 'Client name is over 35 characters.';
    }

    if ($client_egn === '') {
        $errors[] = 'Client EGN is empty.';
    } elseif (mb_strlen($client_egn) > 10) {
        $errors[] = 'Client EGN is over 10 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE client_egn = ?');
        $stmt->execute([$client_egn]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Client EGN already exists.';
        }
    }

    if ($client_phone === '') {
        $errors[] = 'Client phone is empty.';
    } elseif (mb_strlen($client_phone) > 13) {
        $errors[] = 'Client phone is over 13 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE client_phone = ?');
        $stmt->execute([$client_phone]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Client phone already exists.';
        }
    }

    if ($address_id === null) {
        $errors[] = 'Address is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM addresses WHERE address_id = :id');
        $stmt->execute([
            'id' => $address_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'Address with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO clients (client_name, client_egn, client_phone, address_id) VALUES (:name, :egn, :phone, :address_id)');
            $stmt->execute([
                'name' => $client_name,
                'egn' => $client_egn,
                'phone' => $client_phone,
                'address_id' => $address_id,
            ]);
            header('Location: clients.php?action=list&success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Somethinng went wrong please try again!';
        }
    }

    $row['client_name'] = $client_name;
    $row['client_egn'] = $client_egn;
    $row['client_phone'] = $client_phone;
    $row['address_id'] = $address_id;
}

/**
 * ========================
 *  UPDATE (GET REQUEST) => READ ONE
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] == 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE client_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: clients.php?action=list');
        exit;
    }
}

/**
 * ========================
 *  UPDATE (POST REQUEST)
 * ========================
 */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_egn = trim($_POST['client_egn'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    $address_id = isset($_POST['address_id']) ? (int) $_POST['address_id'] : null;

    if ($client_name === '') {
        $errors[] = 'Client name is empty.';
    } elseif (mb_strlen($client_name) > 35) {
        $errors[] = 'Client name is over 35 characters.';
    }

    if ($client_egn === '') {
        $errors[] = 'Client EGN is empty.';
    } elseif (mb_strlen($client_egn) > 10) {
        $errors[] = 'Client EGN is over 10 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE client_egn = :egn AND client_id != :id');
        $stmt->execute([
            'egn' => $client_egn,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Client EGN already exists.';
        }
    }

    if ($client_phone === '') {
        $errors[] = 'Client phone is empty.';
    } elseif (mb_strlen($client_phone) > 13) {
        $errors[] = 'Client phone is over 13 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE client_phone = :phone AND client_id != :id');
        $stmt->execute([
            'phone' => $client_phone,
            'id' => $id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Client phone already exists.';
        }
    }

    if ($address_id === null) {
        $errors[] = 'Address is not chosen.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM addresses WHERE address_id = :id');
        $stmt->execute([
            'id' => $address_id,
        ]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'Address with this ID does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE clients SET client_name = :name , client_egn = :egn , client_phone = :phone , address_id = :address_id  WHERE client_id = :client_id');
            $stmt->execute([
                'client_id' => $id,
                'name' => $client_name,
                'egn' => $client_egn,
                'phone' => $client_phone,
                'address_id' => $address_id
            ]);
            header('Location: clients.php?action=list&success=updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while updating!';
        }
    }

    $row['client_id'] = $id;
    $row['client_name'] = $client_name;
    $row['client_egn'] = $client_egn;
    $row['client_phone'] = $client_phone;
    $row['address_id'] = $address_id;
}

/**
 * ========================
 *  DELETE (POST REQUEST)
 * ========================
 */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM clients WHERE client_id = ?');
        $stmt->execute([$id]);
        header('Location: clients.php?action=list&success=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: clients.php?action=list&error=fk');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script type="module" src="/assets/script.js" defer></script>
</head>
<body>
    <?php loadPartial('header'); ?>

    <div class="page">
        <h1>Clients Table</h1>
        
<?php

/**
 * ============================
 *  READ ALL - TABLE View
 * ============================
 */
?>
        <?php if ($action === 'list'): ?>
           <div class="breadcrumb">
                <a href="clients.php?action=list">Clients</a>
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
                    <a href="clients.php?action=create" class="btn btn-primary">+ Add New Client</a>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <p>No clients found.</p>
                    <a href="clients.php?action=create" class="btn btn-outline">Add the first one.</a>
                </div>
            <?php else: ?>    
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>EGN</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="id-cell"><?= $record['client_id']; ?></td>
                                <td><?= htmlspecialchars($record['client_name']); ?></td>
                                <td><?= htmlspecialchars($record['client_egn']); ?></td>
                                <td><?= htmlspecialchars($record['client_phone']); ?></td>
                                <td><?= htmlspecialchars($record['address_name']); ?></td>
                                <td class="actions-cell">
                                    <a href="clients.php?action=edit&id=<?= $record['client_id']; ?>" class="btn btn-outline">Edit</a>
                                    <form 
                                    action="clients.php?action=delete&id=<?= $record['client_id']; ?>" 
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
            <a href="clients.php?action=list">Clients</a>
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
            <h2>Add New Client</h2>

            <form action="clients.php?action=create" method="POST">
                <div class="form-group">
                    <label for="client_name">Client Name</label>
                    <input 
                    type="text" 
                    name="client_name" 
                    value="<?= htmlspecialchars($row['client_name']); ?>"
                    id="client_name"
                    autocomplete="off"
                    maxlength="35"
                    placeholder="Name"
                    >
                    <p class="form-hint">Maximum 35 characters.</p>
                </div>

                <div class="form-group">
                    <label for="client_egn">Client EGN</label>
                    <input 
                    type="text" 
                    name="client_egn" 
                    value="<?= htmlspecialchars($row['client_egn']); ?>"
                    id="client_egn"
                    autocomplete="off"
                    maxlength="10"
                    placeholder="EGN"
                    >
                    <p class="form-hint">Maximum 10 characters.</p>
                </div>

                <div class="form-group">
                    <label for="client_phone">Client phone</label>
                    <input 
                    type="tel" 
                    name="client_phone" 
                    value="<?= htmlspecialchars($row['client_phone']); ?>"
                    id="client_phone"
                    autocomplete="off"
                    maxlength="13"
                    placeholder="phone"
                    >
                    <p class="form-hint">Maximum 13 characters.</p>
                </div>

                <div class="form-group">
                    <label for="address_id">Client Address</label>
                    <select name="address_id" id="address_id">
                        <?php foreach ($addresses as $address): ?>
                            <option value="<?= $address['address_id'] ?>" 
                            <?= ($row['address_id'] == $address['address_id']) ? 'selected' : '' ?>>
                                <?= $address['address_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Client</button>
                    <a href="clients.php?action=list" class="btn btn-ghost">Cancel</a>
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
            <a href="clients.php?action=list">Clients</a>
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
            <h2>Edit Client</h2>

            <form action="clients.php?action=edit&id=<?= (int) $row['client_id'] ?>" method="POST">
                <div class="form-group">
                    <label for="client_name">Client Name</label>
                    <input 
                    type="text" 
                    name="client_name" 
                    value="<?= htmlspecialchars($row['client_name']); ?>"
                    id="client_name"
                    autocomplete="off"
                    maxlength="35"
                    placeholder="Name"
                    >
                    <p class="form-hint">Maximum 35 characters.</p>
                </div>

                <div class="form-group">
                    <label for="client_egn">Client EGN</label>
                    <input 
                    type="text" 
                    name="client_egn" 
                    value="<?= htmlspecialchars($row['client_egn']); ?>"
                    id="client_egn"
                    autocomplete="off"
                    maxlength="10"
                    placeholder="EGN"
                    >
                    <p class="form-hint">Maximum 10 characters.</p>
                </div>

                <div class="form-group">
                    <label for="client_phone">Client phone</label>
                    <input 
                    type="tel" 
                    name="client_phone" 
                    value="<?= htmlspecialchars($row['client_phone']); ?>"
                    id="client_phone"
                    autocomplete="off"
                    maxlength="13"
                    placeholder="phone"
                    >
                    <p class="form-hint">Maximum 13 characters.</p>
                </div>

                <div class="form-group">
                    <label for="address_id">Client Address</label>
                    <select name="address_id" id="address_id">
                        <?php foreach ($addresses as $address): ?>
                            <option value="<?= $address['address_id'] ?>" 
                            <?= ($row['address_id'] == $address['address_id']) ? 'selected' : '' ?>>
                                <?= $address['address_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Client</button>
                    <a href="clients.php?action=list" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>