<?php
/**
 * JWT Authentication Service
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JwtService
{
    private $secret;
    private $expiry;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->expiry = $_ENV['JWT_EXPIRY'];
    }

    /**
     * Generate JWT token
     * @param array $payload Token payload
     * @return string JWT token
     */
    public function generateToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiry;

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire
        ]);

        return JWT::encode($tokenPayload, $this->secret, 'HS256');
    }

    /**
     * Validate JWT token
     * @param string $token JWT token
     * @return array|null Decoded token payload or null if invalid
     */
    public function validateToken(string $token): ?array
    {
        try {
            return (array) JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (ExpiredException $e) {
            return null;
        } catch (SignatureInvalidException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get token from Authorization header
     * @return string|null Token or null if not found
     */
    public function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return null;
        }

        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }
} 