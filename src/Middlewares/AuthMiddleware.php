<?php
/**
 * Authentication Middleware
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Middlewares;

use App\Services\JwtService;
use App\Utils\Response;
use App\Services\AuthService;
use App\Utils\Logger;

class AuthMiddleware {
    private $jwtService;
    private AuthService $authService;
    private Logger $logger;
    
    public function __construct() {
        $this->jwtService = new JwtService();
        $this->authService = new AuthService();
        $this->logger = new Logger();
    }
    
    /**
     * Handle authentication
     * @return array|null User data or null if not authenticated
     */
    public function handle(): ?array {
        $token = $this->jwtService->getTokenFromHeader();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'No token provided']);
            exit;
        }

        $payload = $this->jwtService->validateToken($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        return $payload;
    }
    
    /**
     * تطبيق المصادقة ثم استمرار التنفيذ إذا نجحت
     */
    public function authenticate(callable $next, array $options = []): callable {
        return function () use ($next, $options) {
            $payload = $this->handle();
            
            if ($payload !== null) {
                // إذا كانت هناك أدوار مطلوبة، تحقق منها
                if (!empty($options['roles'])) {
                    $userRole = $payload['role'] ?? 'user';
                    
                    if (!in_array($userRole, $options['roles'])) {
                        $this->logger->warning('Authorization failed: Insufficient permissions', [
                            'user_id' => $payload['user_id'],
                            'role' => $userRole,
                            'required_roles' => $options['roles']
                        ]);
                        
                        Response::error('Forbidden: Insufficient permissions', 403);
                        return;
                    }
                }
                
                // تمرير معلومات المستخدم إلى الدالة التالية
                call_user_func($next, $payload);
            }
        };
    }
}