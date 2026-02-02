<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utils\Response;
use App\Controllers\UserController;
use App\Controllers\ItemController;
use App\Controllers\BidController;
use App\Controllers\TransactionController;
use App\Controllers\AuctionStatusController;
use App\Controllers\ImageController;
use App\Controllers\ReviewController;
use App\Controllers\WatchlistController;
use App\Controllers\AdminController;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Parse request body for POST/PUT requests
$requestBody = null;
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $requestBody = json_decode(file_get_contents('php://input'), true);
}

// Parse query parameters
$queryParams = [];
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);

// Simple router
try {
    // Health check endpoint
    if ($uri === 'health' && $method === 'GET') {
        Response::success([
            'status' => 'ok',
            'message' => 'Auction Portal Backend is running'
        ]);
    }

    // Root endpoint
    if ($uri === '' && $method === 'GET') {
        Response::success([
            'message' => 'Auction Portal API',
            'version' => '1.0.0',
            'technology' => 'PHP + MySQL'
        ]);
    }

    // User routes
    if ($uri === 'api/users/register' && $method === 'POST') {
        $controller = new UserController();
        $controller->register($requestBody ?? []);
    }

    if ($uri === 'api/users/login' && $method === 'POST') {
        $controller = new UserController();
        $controller->login($requestBody ?? []);
    }

    if ($uri === 'api/users/profile' && $method === 'GET') {
        $controller = new UserController();
        $controller->getProfile();
    }

    if ($uri === 'api/users/profile' && $method === 'PUT') {
        $controller = new UserController();
        $controller->updateProfile($requestBody ?? []);
    }

    if (preg_match('#^api/users/(\d+)/public$#', $uri, $matches) && $method === 'GET') {
        $controller = new UserController();
        $controller->getPublicProfile((int)$matches[1]);
    }

    // Item routes
    if ($uri === 'api/items' && $method === 'POST') {
        $controller = new ItemController();
        $controller->create($requestBody ?? []);
    }

    if ($uri === 'api/items' && $method === 'GET') {
        $controller = new ItemController();
        $controller->getAll($queryParams);
    }

    if (preg_match('#^api/items/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new ItemController();
        $controller->getById((int)$matches[1]);
    }

    // Image routes
    if (preg_match('#^api/items/(\d+)/images$#', $uri, $matches) && $method === 'POST') {
        $controller = new ImageController();
        $controller->upload((int)$matches[1]);
    }

    if (preg_match('#^api/items/(\d+)/images$#', $uri, $matches) && $method === 'GET') {
        $controller = new ImageController();
        $controller->getImages((int)$matches[1]);
    }

    if (preg_match('#^api/images/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
        $controller = new ImageController();
        $controller->delete((int)$matches[1]);
    }

    // Review routes
    if ($uri === 'api/reviews' && $method === 'POST') {
        $controller = new ReviewController();
        // Get authenticated user ID from middleware
        $token = \App\Utils\Auth::getTokenFromHeader();
        if (!$token) {
            Response::json(['error' => 'Authentication required'], 401);
            exit;
        }
        $payload = \App\Utils\Auth::verifyToken($token);
        if (!$payload) {
            Response::json(['error' => 'Invalid token'], 401);
            exit;
        }
        $controller->create($requestBody ?? [], $payload['userId']);
    }

    if (preg_match('#^api/users/(\d+)/reviews$#', $uri, $matches) && $method === 'GET') {
        $controller = new ReviewController();
        $controller->getUserReviews((int)$matches[1]);
    }

    if (preg_match('#^api/users/(\d+)/rating$#', $uri, $matches) && $method === 'GET') {
        $controller = new ReviewController();
        $controller->getUserRating((int)$matches[1]);
    }

    // Watchlist routes
    if ($uri === 'api/watchlist' && $method === 'POST') {
        $controller = new WatchlistController();
        // Get authenticated user ID from middleware
        $token = \App\Utils\Auth::getTokenFromHeader();
        if (!$token) {
            Response::json(['error' => 'Authentication required'], 401);
            exit;
        }
        $payload = \App\Utils\Auth::verifyToken($token);
        if (!$payload) {
            Response::json(['error' => 'Invalid token'], 401);
            exit;
        }
        $controller->add($requestBody ?? [], $payload['userId']);
    }

    if (preg_match('#^api/watchlist/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
        $controller = new WatchlistController();
        // Get authenticated user ID from middleware
        $token = \App\Utils\Auth::getTokenFromHeader();
        if (!$token) {
            Response::json(['error' => 'Authentication required'], 401);
            exit;
        }
        $payload = \App\Utils\Auth::verifyToken($token);
        if (!$payload) {
            Response::json(['error' => 'Invalid token'], 401);
            exit;
        }
        $controller->remove((int)$matches[1], $payload['userId']);
    }

    if ($uri === 'api/watchlist' && $method === 'GET') {
        $controller = new WatchlistController();
        // Get authenticated user ID from middleware
        $token = \App\Utils\Auth::getTokenFromHeader();
        if (!$token) {
            Response::json(['error' => 'Authentication required'], 401);
            exit;
        }
        $payload = \App\Utils\Auth::verifyToken($token);
        if (!$payload) {
            Response::json(['error' => 'Invalid token'], 401);
            exit;
        }
        $controller->getWatchlist($payload['userId']);
    }

    if (preg_match('#^api/watchlist/check/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new WatchlistController();
        // Get authenticated user ID from middleware
        $token = \App\Utils\Auth::getTokenFromHeader();
        if (!$token) {
            Response::json(['error' => 'Authentication required'], 401);
            exit;
        }
        $payload = \App\Utils\Auth::verifyToken($token);
        if (!$payload) {
            Response::json(['error' => 'Invalid token'], 401);
            exit;
        }
        $controller->checkWatching((int)$matches[1], $payload['userId']);
    }

    // Bid routes
    if ($uri === 'api/bids' && $method === 'POST') {
        $controller = new BidController();
        $controller->create($requestBody ?? []);
    }

    if (preg_match('#^api/bids/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new BidController();
        $controller->getHistory((int)$matches[1]);
    }

    // Transaction routes
    if ($uri === 'api/transactions' && $method === 'GET') {
        $controller = new TransactionController();
        $controller->getAll();
    }

    if (preg_match('#^api/transactions/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new TransactionController();
        $controller->getById((int)$matches[1]);
    }

    // Auction Status routes (for real-time price updates)
    if (preg_match('#^api/auction-status/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new AuctionStatusController();
        $controller->getAuctionStatus((int)$matches[1]);
    }

    if ($uri === 'api/auction-status/multiple' && $method === 'GET') {
        $controller = new AuctionStatusController();
        $controller->getMultipleAuctionStatus($queryParams);
    }

    if (preg_match('#^api/price-history/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller = new AuctionStatusController();
        $controller->getPriceHistory((int)$matches[1]);
    }

    // Admin routes
    if ($uri === 'api/admin/stats' && $method === 'GET') {
        $controller = new AdminController();
        $controller->getStatistics();
    }

    if ($uri === 'api/admin/users' && $method === 'GET') {
        $controller = new AdminController();
        $controller->getAllUsers($queryParams);
    }

    if (preg_match('#^api/admin/users/(\d+)/role$#', $uri, $matches) && $method === 'PUT') {
        $controller = new AdminController();
        $controller->updateUserRole((int)$matches[1], $requestBody ?? []);
    }

    if (preg_match('#^api/admin/users/(\d+)/suspend$#', $uri, $matches) && $method === 'POST') {
        $controller = new AdminController();
        $controller->suspendUser((int)$matches[1], $requestBody ?? []);
    }

    if (preg_match('#^api/admin/users/(\d+)/ban$#', $uri, $matches) && $method === 'POST') {
        $controller = new AdminController();
        $controller->banUser((int)$matches[1]);
    }

    if (preg_match('#^api/admin/users/(\d+)/reactivate$#', $uri, $matches) && $method === 'POST') {
        $controller = new AdminController();
        $controller->reactivateUser((int)$matches[1]);
    }

    if (preg_match('#^api/admin/items/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
        $controller = new AdminController();
        $controller->deleteItem((int)$matches[1]);
    }

    // If no route matched
    if (!isset($controller)) {
        Response::notFound('Endpoint not found');
    }

} catch (\Exception $e) {
    if ($_ENV['APP_DEBUG'] === 'true') {
        Response::serverError($e->getMessage());
    } else {
        Response::serverError('An unexpected error occurred');
    }
}
