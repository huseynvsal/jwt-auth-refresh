# JWT Auth Refresh Package

This Laravel package provides JWT authentication with access and refresh token functionality. It includes all the necessary logic for token generation, token refresh, and user logout without the need to write any complex controller or middleware.

## Features

- **JWT Access and Refresh Tokens**: Generate access and refresh tokens during login.
- **Token Refresh**: Refresh tokens if the refresh token is valid.
- **Logout**: Remove all refresh tokens during logout.
- **Custom Guards**: Automatically registers a custom JWT guard.
- **Configuration**: Easily configure token expiration times and secret keys.

## Installation

### Step 1: Install the Package

Install the package using Composer.

```bash
composer require huseynvsal/jwt-auth-refresh
```

### Step 2: Publish Resources

After installing the package, you need to publish the configuration and migration files.

Run the following command to publish the resources:

```bash
php artisan vendor:publish --tag=jwt-auth-config
php artisan vendor:publish --tag=jwt-auth-migrations
```

This will publish:

* `config/jwt-auth.php` – The configuration file for JWT tokens (secret keys and expiration times).
* A migration file to create `refresh_tokens` tables.

### Step 3: Run Migrations

Run the migration to create the necessary table for storing refresh tokens.

```bash
php artisan migrate
```

### Step 4: Configure `.env` File

In your .env file, add the following configuration for JWT tokens:

```env
JWT_SECRET_KEY=your-secret-key-here
JWT_REFRESH_SECRET_KEY=your-refresh-secret-key-here
JWT_ACCESS_TOKEN_EXPIRATION=3600  # 1 hour
JWT_REFRESH_TOKEN_EXPIRATION=604800  # 7 days
```

* `JWT_SECRET_KEY`: The secret key used to sign JWT tokens. Ensure you keep it secure.
* `JWT_SECRET_KEY`: The secret key used to sign refresh tokens. Ensure you keep it secure.
* `JWT_ACCESS_TOKEN_EXPIRATION`: The expiration time for access tokens (in seconds).
* `JWT_REFRESH_TOKEN_EXPIRATION`: The expiration time for refresh tokens (in seconds).

### Step 5: Set Guard in `config/auth.php`

Your package will automatically register the custom jwt guard for authentication. The next step is to set it in the config/auth.php file.

Ensure that your config/auth.php is configured to use the jwt guard for API authentication.

```php
// config/auth.php

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users'
    ],

    'api' => [
        'driver' => 'jwt',
        'provider' => 'users'
    ]
]
```
This sets the default guard for API requests to use the custom jwt guard provided by the package.

### Step 6: Update User Model (Optional)

If you want to use the JWT-based authentication with your own `User` model, ensure that your User model implements `Illuminate\Contracts\Auth\Authenticatable` and have `refreshTokens` relation defined:

Example:

```php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Huseynvsal\JwtAuthRefresh\Models\AccessToken;
use Huseynvsal\JwtAuthRefresh\Models\RefreshToken;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class, 'user_id');
    }

    // Additional user logic...
}
```
This is required so that Laravel can authenticate the user using the JWT guard.

---

## Usage

### Step 1: Login and Generate Tokens

In your `AuthController`, you can use the `JwtAuthService` to generate both the access and refresh tokens when the user logs in.

Example login method:

```php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use Huseynvsal\JwtAuthRefresh\Exceptions\InvalidTokenException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Huseynvsal\JwtAuthRefresh\Services\JwtAuthService;

class AuthController extends Controller
{
    protected JwtAuthService $jwtAuthService;

    public function __construct(JwtAuthService $jwtAuthService)
    {
        $this->jwtAuthService = $jwtAuthService;
    }

    public function login(): JsonResponse
    {
        $user = User::find(1);

        $accessToken = $this->jwtAuthService->generateAccessToken($user);
        $refreshToken = $this->jwtAuthService->generateRefreshToken($user);

        return response()->json([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken
        ]);
    }
}
```

### Step 2: Refresh Tokens

You can create an API to allow users to refresh their tokens using a valid refresh token.

Example refresh method:

```php
// app/Http/Controllers/AuthController.php

public function refresh(Request $request): JsonResponse
{
    try
    {
        $tokens = $this->jwtAuthService->refreshTokens($request->input('refreshToken'));

        return response()->json($tokens);
    }
    catch (InvalidTokenException $e)
    {
        return response()->json(['error' => $e->getMessage()], 401);
    }
}
```

### Step 3: Logout and Revoke Tokens

To handle logout, you can delete the user's access and refresh tokens:

```php
// app/Http/Controllers/AuthController.php

public function logout(): JsonResponse
{
    $user = auth()->user();
    $this->jwtAuthService->revokeTokensForUser($user);

    return response()->json(['message' => 'Logged out successfully']);
}
```

---

## Additional Information
* **JWT Tokens:** The refresh tokens will be stored in the refresh_tokens tables.
* **Guard Usage:** The jwt guard is automatically registered, and you can use the auth() helper to authenticate users using JWT tokens in your controllers.
* **Expiration:** Both access and refresh tokens have configurable expiration times defined in the config/jwt-auth.php file.