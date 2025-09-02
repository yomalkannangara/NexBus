<?php
// PSR-4-ish autoloader for App\*, preserving your case (lowercase dirs).

spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = dirname(__DIR__) . '/';   // project root (parent of controllers/, models/, ...)

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return; // not our namespace

    // Remove prefix and map \ -> /, no case changes
    $relative = substr($class, $len);                 // e.g. controllers\NtcAdminController
    $path     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    // basic safety
    if (str_contains($relative, '..')) return;

    if (is_file($path)) {
        require $path;
    }
});
