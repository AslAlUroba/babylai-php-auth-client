# BabylAI PHP Auth Client

A simple PHP library to fetch a client token from the [BabylAI Auth API](https://babylai.net). It wraps a single endpoint:

```
POST https://babylai.net/api/Auth/client/get-token
```

by accepting a Tenant ID (UUID) and API key, returning a JSON payload containing `token` and `expiresIn`.

## Table of Contents

- [Features](#features)  
- [Requirements](#requirements)  
- [Installation](#installation)  
- [Usage](#usage)  
  - [Standalone PHP Example](#standalone-php-example)  
  - [Laravel Integration](#laravel-integration)  
- [Configuration](#configuration)  
- [Contributing](#contributing)  
- [License](#license)  

## Features

- **PSR-4 autoloading**: simply `require` via Composer.  
- **Guzzle-powered**: uses [Guzzle HTTP](https://github.com/guzzle/guzzle) under the hood.  
- **Lightweight**: only two DTO classes plus the client wrapper.  
- **Error handling**: throws an exception if the HTTP request fails or the response is invalid.

## Requirements

- PHP >= 7.4  
- `ext-curl` / `ext-openssl` enabled (for HTTPS)  
- [Guzzle 7.x](https://packagist.org/packages/guzzlehttp/guzzle) (installed via Composer)  

## Installation

1. **Ensure you have Composer installed** on your system:  
   ```bash
   composer --version
   ```

2. **Require this package**
   ```bash
   composer require aslaluroba/babylai_php_auth_client
   ```

   This will pull in Guzzle as well and configure PSR-4 autoloading for BabylAI\* classes.

3. **Verify that vendor/aslaluroba/babylai_php_auth_client exists and that vendor/autoload.php was updated.**

## Usage

Below are two common ways to use this client:

### Standalone PHP Example

Create a simple test.php in any folder outside Laravel:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use BabylAI\Client\BabylAiAuthClient;

$tenantId = '#####';  // replace with your TenantId
$apiKey   = '#####';  // replace with your API key

$client = new BabylAiAuthClient('https://babylai.net/api/');

try {
    $resp = $client->getClientToken($tenantId, $apiKey);
    echo "Token: " . $resp->getToken() . PHP_EOL;
    echo "Expires in: " . $resp->getExpiresIn() . " seconds" . PHP_EOL;
} catch (\Exception $ex) {
    echo "Error: " . $ex->getMessage() . PHP_EOL;
}
```

Run it:
```bash
php test.php
```

If credentials are valid, you'll see:

```
Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9…
Expires in: 900 seconds
```

Otherwise, you'll get an exception message.

### Laravel Integration

Below is a minimal Laravel 12 example showing how to create an endpoint `/api/Token` that returns `{ token, expiresIn }`:

1. **Install in a Laravel project**

   From your Laravel app root:
   ```bash
   composer require aslaluroba/babylai_php_auth_client
   ```

2. **Create configuration file**

   Create `config/babylai.php` with:

   ```php
   <?php
   return [
       'tenant_id' => env('BABYLAI_TENANT_ID', ''),
       'api_key'   => env('BABYLAI_API_KEY', ''),
       'base_url'  => env('BABYLAI_BASE_URL', 'https://babylai.net/api/'),
   ];
   ```

   Add these to `.env`:

   ```ini
   BABYLAI_TENANT_ID=2176c188-8cb4-4fc3-8366-2d32e601f7e5
   BABYLAI_API_KEY="I/22UK34I6xAhglfPXTwPAgUuVyJw8TdnG26ZLI5gYQ="
   BABYLAI_BASE_URL="https://babylai.net/api/"
   ```

   Run:
   ```bash
   php artisan config:clear
   ```

3. **Bind the client in the container**

   Open `app/Providers/AppServiceProvider.php`, and in the `register()` method add:

   ```php
   use BabylAI\Client\BabylAiAuthClient;
   use GuzzleHttp\Client as GuzzleClient;

   public function register(): void
   {
       $this->app->singleton(BabylAiAuthClient::class, function ($app) {
           $cfg = $app->config->get('babylai');
           $tenantId = $cfg['tenant_id'];
           $apiKey   = $cfg['api_key'];
           $baseUrl  = $cfg['base_url'];

           $guzzle = new GuzzleClient([
               'base_uri' => rtrim($baseUrl, '/') . '/'
           ]);

           return new BabylAiAuthClient($baseUrl, $guzzle);
       });
   }
   ```

   Save the file.

4. **Create the controller**

   Generate a controller:
   ```bash
   php artisan make:controller Api/TokenController
   ```

   Edit `app/Http/Controllers/Api/TokenController.php`:

   ```php
   <?php

   namespace App\Http\Controllers\Api;

   use App\Http\Controllers\Controller;
   use BabylAI\Client\BabylAiAuthClient;
   use Illuminate\Http\JsonResponse;
   use Illuminate\Support\Facades\Log;
   use Exception;

   class TokenController extends Controller
   {
       private BabylAiAuthClient $authClient;

       public function __construct(BabylAiAuthClient $authClient)
       {
           $this->authClient = $authClient;
       }

       public function getClientToken(): JsonResponse
       {
           try {
               $cfg = config('babylai');
               $tenantId = (string) $cfg['tenant_id'];
               $apiKey   = (string) $cfg['api_key'];

               $resp = $this->authClient->getClientToken($tenantId, $apiKey);

               return response()->json([
                   'token'     => $resp->getToken(),
                   'expiresIn' => $resp->getExpiresIn(),
               ], 200);
           } catch (Exception $ex) {
               Log::error('BabylAI TokenController: ' . $ex->getMessage());
               $prev = $ex->getPrevious();
               if ($prev && method_exists($prev, 'getResponse')) {
                   $body = (string) $prev->getResponse()->getBody();
                   return response()->json([
                       'error'   => 'Upstream error',
                       'details' => $body,
                   ], 502);
               }
               return response()->json([
                   'error' => $ex->getMessage()
               ], 500);
           }
       }
   }
   ```

5. **Register the route**

   If `routes/api.php` does not exist, create it. In `routes/api.php`, add:

   ```php
   <?php
   use Illuminate\Support\Facades\Route;
   use App\Http\Controllers\Api\TokenController;

   Route::get('Token', [TokenController::class, 'getClientToken']);
   ```

   Note: This registers `/api/Token` (capital "T").
   If you prefer lowercase, use 'token' instead of 'Token'.

   Run:
   ```bash
   php artisan route:clear
   php artisan route:list
   ```

   You should see:
   ```
   GET|HEAD  | api/Token  | App\Http\Controllers\Api\TokenController@getClientToken
   ```

6. **Configure CORS**

   Create `config/cors.php` (if it doesn't exist) with:

   ```php
   <?php
   return [
       'paths' => ['api/*'],
       'allowed_methods' => ['*'],
       'allowed_origins' => [
           'http://localhost:4200',
           'https://localhost:4200',
           'http://localhost:3000',
           'https://localhost:3000',
           'http://localhost:5173',
           'https://localhost:5173',
       ],
       'allowed_origins_patterns' => [],
       'allowed_headers' => ['*'],
       'exposed_headers' => [],
       'max_age' => 0,
       'supports_credentials' => false,
   ];
   ```

   Ensure `\Fruitcake\Cors\HandleCors::class` is in `app/Http/Kernel.php` under `$middleware`. Then run:

   ```bash
   php artisan config:clear
   ```

7. **Serve over HTTPS on port 7075**

   If you have the Symfony CLI installed, in a new terminal:

   ```bash
   cd C:/Users/Ahmed/Desktop/babylai-laravel-app
   symfony server:start --port=7075
   ```

   This auto‐generates a local TLS certificate. It will show:

   ```
   [OK] Web server listening  
        https://127.0.0.1:7075
   ```

   Open your browser at:
   ```
   https://localhost:7075/api/Token
   ```

   Accept the (self-signed) certificate, and you'll see:

   ```json
   {
     "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.…",
     "expiresIn": 900
   }
   ```

   Your Angular app at `https://localhost:4200` can now fetch from `https://localhost:7075/api/Token` without CORS issues.

## Configuration

- `composer.json` (defaults for your package)

- `.env` (in Laravel) must have:
  ```ini
  BABYLAI_TENANT_ID=...
  BABYLAI_API_KEY="..."
  BABYLAI_BASE_URL="https://babylai.net/api/"
  SESSION_DRIVER=file
  CACHE_STORE=file
  QUEUE_CONNECTION=sync
  ```

- `config/babylai.php` lives in Laravel's `config/` folder.

- `config/cors.php` whitelists your front‐end origins.

- Your route (`api/Token`) is registered exactly as uppercase "Token" if you need that URL shape.

## Contributing

Fork the repository on GitHub:
https://github.com/your-vendor/babylai-php-auth-client

Create a feature branch:
```bash
git checkout -b feature/my-new-feature
```

Commit your changes, respecting PSR-12 formatting.

Push to your fork and submit a Pull Request.

We use semantic versioning (v1.0.0, v1.1.0, etc.). Tag new releases accordingly.

## License

AAU