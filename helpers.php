<?php
session_start();

function requireLogin()
{
    if (!isset($_SESSION['employee_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function inspect($variable): void
{
    echo '<pre>';
    echo var_dump($variable);
    echo '</pre>';
}

function inspectAndDie($value)
{
    echo '<pre>';
    var_dump($value);
    echo '</pre>';
    die();
}

function basePath($path = '')
{
    return __DIR__ . '\\' . $path;
}

function loadPartial($name)
{
    $partialPath = basePath("includes/{$name}.php");
    if (file_exists($partialPath)) {
        require $partialPath;
    } else {
        echo "Partial $name not found";
    }
}
