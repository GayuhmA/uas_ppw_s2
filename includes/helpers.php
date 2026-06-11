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

function versioned_asset($path)
{
    $relativePath = ltrim($path, '/');
    $filePath = dirname(__DIR__) . '/' . $relativePath;
    $version = file_exists($filePath) ? filemtime($filePath) : time();

    return asset($relativePath) . '?v=' . $version;
}

function active_nav($path)
{
    $currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $targetPath = trim(url($path), '/');

    return $currentPath === $targetPath ? 'active' : '';
}

function active_nav_group($paths)
{
    $currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    foreach ($paths as $path) {
        $targetPath = trim(url($path), '/');

        if ($currentPath === $targetPath) {
            return 'active';
        }
    }

    return '';
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}
