<?php

namespace App\Models;

use PHPFramework\Model;

class User extends Model
{
    protected string $table = 'users';
    // The users table currently has created_at but no updated_at column.
    protected bool $timestamps = false;
    protected array $loaded = ['name', 'email', 'password', 'confirmPassword',];

    protected array $fillable = ['name', 'email', 'password',];

    protected array $rules = [
        'required' => ['name', 'email', 'password', 'confirmPassword'],
        'email' => ['email'],
        'lengthMin' => [['password', 6]],
        'equals' => [['password', 'confirmPassword']],
        'uniqueName' => [
            ['name', 'users,name',],
    ],
        'uniqueEmail' => [
            ['email', 'users,email',],
    ],
    ];
}
