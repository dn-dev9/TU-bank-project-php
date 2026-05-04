<?php
require_once 'helpers.php';

?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Bank Project</title>
        <link rel="stylesheet" href="/assets/style.css" />
        <script type="module" src="assets/script.js" defer></script>
    </head>
    <body>
        <div class="wrapper__home">
            <?php loadPartial('header'); ?>
            <section class="section__intro">
                <div class="section__intro__content">
                    <h3>Welcome!</h3>
                    <h1>The best bank to manage your finances</h1>
                    <p>
                        This is a PHP project on task 3 (Bank PHP APP) using HTML, CSS, JS, PHP, MySQL. Written by
                        Daniel Nikolov 24621602.
                    </p>
                    <?php if (!isset($_SESSION['employee_name'])): ?>
                        <a href="/login.php" class="btn btn-secondary">
                            <span>Log In</span>
                        </a>
                    <?php endif; ?>
                </div>
            </section>
            <?php loadPartial('footer'); ?>
        </div>
    </body>
</html>
