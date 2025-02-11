<?php

namespace Huseynvsal\JwtAuthRefresh\Guards;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Huseynvsal\JwtAuthRefresh\Services\JwtAuthService;

class JwtGuard implements Guard
{
    use GuardHelpers;

    protected JwtAuthService $jwtAuthService;
    protected Request $request;

    public function __construct(UserProvider $provider, JwtAuthService $jwtAuthService, Request $request)
    {
        $this->provider = $provider;
        $this->jwtAuthService = $jwtAuthService;
        $this->request = $request;
    }

    public function user(): ?Authenticatable
    {
        if ($this->user)
        {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if (!$token)
        {
            return null;
        }

        $decoded = $this->jwtAuthService->validateAccessToken($token);

        if (!$decoded || !isset($decoded->sub))
        {
            return null;
        }

        return $this->user = $this->provider->retrieveById($decoded->sub);
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    protected function getTokenFromRequest(): ?string
    {
        $authorizationHeader = $this->request->header('Authorization');

        if (!$authorizationHeader || !preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches))
        {
            return null;
        }

        return $matches[1];
    }
}
