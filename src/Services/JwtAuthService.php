<?php

namespace Huseynvsal\JwtAuthRefresh\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use Huseynvsal\JwtAuthRefresh\Models\RefreshToken;
use Huseynvsal\JwtAuthRefresh\Exceptions\InvalidTokenException;

class JwtAuthService
{
    private string $accessSecret;
    private string $refreshSecret;
    private int $accessTokenExpireTime;
    private int $refreshTokenExpireTime;

    public function __construct()
    {
        $this->accessSecret = config('jwt-auth.secret_key');
        $this->refreshSecret = config('jwt-auth.refresh_secret_key');
        $this->accessTokenExpireTime = config('jwt-auth.access_token_expiration');
        $this->refreshTokenExpireTime = config('jwt-auth.refresh_token_expiration');
    }

    public function generateAccessToken(object $user): string
    {
        return $this->createToken($user->id, $this->accessTokenExpireTime, $this->accessSecret);
    }

    public function generateRefreshToken(object $user): string
    {
        $jti = Str::orderedUuid()->toString();

        $user->refreshTokens()->create([
            'jti' => $jti
        ]);

        return $this->createToken($user->id, $this->refreshTokenExpireTime, $this->refreshSecret, $jti);
    }

    public function validateAccessToken(string $token): ?object
    {
        return $this->decodeToken($token, $this->accessSecret);
    }

    /**
     * @throws InvalidTokenException
     */
    public function validateRefreshToken(string $refreshToken): object
    {
        $decoded = $this->decodeToken($refreshToken, $this->refreshSecret);

        if (!$decoded || empty($decoded->jti))
        {
            throw new InvalidTokenException();
        }

        if (!RefreshToken::where('jti', $decoded->jti)->exists())
        {
            throw new InvalidTokenException('Refresh token not found.');
        }

        return $decoded;
    }

    /**
     * @throws InvalidTokenException
     */
    public function refreshTokens(string $refreshToken): array
    {
        $decoded = $this->validateRefreshToken($refreshToken);

        $user = RefreshToken::where('jti', $decoded->jti)->firstOrFail()->user;

        $newAccessToken = $this->generateAccessToken($user);
        $newRefreshToken = $this->generateRefreshToken($user);

        // Revoke old refresh token
        RefreshToken::where('jti', $decoded->jti)->delete();

        return [
            'accessToken' => $newAccessToken,
            'refreshToken' => $newRefreshToken
        ];
    }

    public function revokeTokensForUser(object $user): void
    {
        $user->refreshTokens()->delete();
    }

    private function createToken(int $userId, int $expiresIn, string $secret, ?string $jti = null): string
    {
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + $expiresIn,
        ];

        if ($jti)
        {
            $payload['jti'] = $jti;
        }

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Decode a JWT token safely.
     */
    private function decodeToken(string $token, string $secret): ?object
    {
        try
        {
            return JWT::decode($token, new Key($secret, 'HS256'));
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    public function getJtiFromToken(string $token): string
    {
        return $this->decodeToken($token, $this->refreshSecret)->jti;
    }
}
