<?php
declare(strict_types=1);

function create_system_password_hash(string $plain_password): string
{
    $plain_password = trim($plain_password);

    if ($plain_password === "") {
        throw new InvalidArgumentException("Password cannot be empty.");
    }

    if (strlen($plain_password) < 6) {
        throw new InvalidArgumentException("Password must have at least 6 characters.");
    }

    return password_hash($plain_password, PASSWORD_DEFAULT);
}

function verify_system_password(string $plain_password, string $password_hash): bool
{
    return password_verify($plain_password, $password_hash);
}