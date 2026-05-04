<?php
require_once 'helpers.php';
require_once basePath('includes/db.php');

/**
 * ========= Log In =========
 *
 * required SQL table query fields:
 *  - text field (Employee Name)
 *  - text field (Employee Phone) - Phone is unique, enough for authentication
 *
 * Form preserves account_name
 * server side validation error messages
 */

/**
 * ========================
 *  VARIABLES
 * ========================
 */
$records = [];
$errors = [];
$employee_phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_phone = trim($_POST['employee_phone'] ?? '');

    if ($employee_phone === '') {
        $errors[] = 'Employee Phone is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
        SELECT employees.* , roles.role_name 
        FROM employees 
        JOIN roles ON employees.role_id = roles.role_id
        WHERE employee_phone = :phone
        ');
        $stmt->execute(['phone' => $employee_phone]);
        $employee = $stmt->fetch();

        if ($employee) {
            $_SESSION['employee_id'] = $employee['employee_id'];
            $_SESSION['employee_name'] = $employee['employee_name'];
            $_SESSION['employee_phone'] = $employee['employee_phone'];
            $_SESSION['employee_role'] = $employee['role_name'];
            header('Location: /index.php');
            exit;
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="header">
    <div class="header__wrapper">
        <a href="/" class="header__logo">
            <img loading="lazy" src="/assets/images/logo-w.png" alt="Logo" />
        </a>
    </div>
</header>

    <div class="page">
        <h1>Log In</h1>

        <div class="breadcrumb">
            <a href="/">home</a>
            <span>></span> Log In
        </div>

         <?php if (!empty($errors)): ?>            
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif ?>

        <div class="form-card form-card--horizontal">
            <h2>Log In</h2>
            
            <form action="login.php" method="POST">
                
               <div class="form-group">
                    <label for="employee_phone">Employee Phone</label>
                    <input 
                    type="text" 
                    name="employee_phone" 
                    value="<?= htmlspecialchars($employee_phone); ?>"
                    id="employee_phone"
                    autocomplete="off"
                    maxlength="13"
                    placeholder="Phone Number"
                    >
                    <p class="form-hint">Maximum 13 characters. Try with <b>"+359567906733"</b></p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Log In</button>
                    <a href="/" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>

    </div>
    <?php loadPartial('footer'); ?>
</body>
</html>
