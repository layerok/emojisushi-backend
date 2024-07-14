# Emojisushi backend

## Requirements
PHP 8.2

## Getting started

1. Create ```.env``` file based on ```.env.example```
2. Edit ```.env```, set your database credentials
3. ```composer install``` (OctoberCms licence key is required)
4. ```php artisan october:migrate```
5. ```php artisan mall:seed-demo```

## Troubleshoot

### 1. SecretMissingException
Cache config to fix this exception,
I don't know why but sometimes env variables aren't loaded in time

### 2. Cannot login user as they are not activated
In the backend area you can allow to login not activated users
