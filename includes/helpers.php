<?php

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_path()
{
    return '/uas_ppw_s2/';
}

function url($path = '')
{
    return base_path() . ltrim($path, '/');
}

function asset($path)
{
    return url($path);
}

function active_nav($path)
{
    $currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $targetPath = trim(url($path), '/');

    return $currentPath === $targetPath ? 'active' : '';
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}
