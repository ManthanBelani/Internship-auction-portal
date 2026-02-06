<?php

// Custom autoloader for App namespace since composer dump-autoload is failing
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($path)) {
            // die("Loading $class from $path");
            require_once $path;
        }
    }
});

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utils\Response;
use App\Router\Router;
use App\Middleware\CorsMiddleware;
use App\Middleware\RateLimiter;
use App\Middleware\AuthMiddleware;
use App\Utils\AppLogger;

// Controllers
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

// Initialize Router
$router = new Router();

// Global Middleware
$router->addGlobalMiddleware(CorsMiddleware::middleware());
$router->addGlobalMiddleware(function() {
    // Basic Request Logging
    AppLogger::info($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'], [
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
    return true;
});
$router->addGlobalMiddleware(RateLimiter::middleware(100, 1)); // 100 requests per minute

// --- Routes ---

// Health Check
$router->get('health', function() {
    Response::success([
        'status' => 'ok',
        'timestamp' => time(),
        'service' => 'Auction Portal Backend'
    ]);
});

// Root
$router->get('', function() {
    Response::success([
        'message' => 'Auction Portal API',
        'version' => '1.1.0',
        'technology' => 'PHP + SQLite'
    ]);
});

// User Routes
$router->post('api/users/register', [new UserController(), 'register']);
$router->post('api/users/login', [new UserController(), 'login']);

$router->get('api/users/profile', [new UserController(), 'getProfile'], [AuthMiddleware::class . '::authenticate']);
$router->put('api/users/profile', [new UserController(), 'updateProfile'], [AuthMiddleware::class . '::authenticate']);
$router->get('api/users/(\d+)/public', [new UserController(), 'getPublicProfile']);

// Item Routes
$router->post('api/items', [new ItemController(), 'create'], [AuthMiddleware::class . '::authenticate']);
$router->get('api/items', [new ItemController(), 'getAll']);
$router->get('api/items/(\d+)', [new ItemController(), 'getById']);

// Image Routes
$router->post('api/items/(\d+)/images', [new ImageController(), 'upload'], [AuthMiddleware::class . '::authenticate']);
$router->get('api/items/(\d+)/images', [new ImageController(), 'getImages']);
$router->delete('api/images/(\d+)', [new ImageController(), 'delete'], [AuthMiddleware::class . '::authenticate']);

// Review Routes
$router->post('api/reviews', function($body) {
    if ($user = AuthMiddleware::authenticate()) {
        (new ReviewController())->create($body ?? [], $user['userId']);
    }
});

$router->get('api/users/(\d+)/reviews', [new ReviewController(), 'getUserReviews']);
$router->get('api/users/(\d+)/rating', [new ReviewController(), 'getUserRating']);

// Watchlist Routes
$router->post('api/watchlist', function($body) {
    if ($user = AuthMiddleware::authenticate()) {
        (new WatchlistController())->add($body ?? [], $user['userId']);
    }
});

$router->get('api/watchlist', function() {
    if ($user = AuthMiddleware::authenticate()) {
        (new WatchlistController())->getWatchlist($user['userId']);
    }
});

$router->delete('api/watchlist/(\d+)', function($itemId) {
    if ($user = AuthMiddleware::authenticate()) {
        (new WatchlistController())->remove((int)$itemId, $user['userId']);
    }
});

$router->get('api/watchlist/check/(\d+)', function($itemId) {
    if ($user = AuthMiddleware::authenticate()) {
        (new WatchlistController())->checkWatching((int)$itemId, $user['userId']);
    }
});

// Bid Routes
$router->post('api/bids', [new BidController(), 'create']); // BidController handles Auth internally
$router->get('api/bids/(\d+)', [new BidController(), 'getHistory']);

// Transaction Routes
$router->get('api/transactions', [new TransactionController(), 'getAll'], [AuthMiddleware::class . '::authenticate']);
$router->get('api/transactions/(\d+)', [new TransactionController(), 'getById'], [AuthMiddleware::class . '::authenticate']);

// --- Buyer/User Role Specific Routes ---
$router->group('api/my', function(Router $r) {
    $buyerController = new \App\Controllers\BuyerController();
    
    $r->get('bids', [$buyerController, 'getMyBids']);
    $r->get('notifications', [$buyerController, 'getNotifications']);
    $r->put('notifications/(\d+)/read', [$buyerController, 'markAsRead']);
    
    $r->post('payments/(\d+)/pay', [new \App\Controllers\TransactionController(), 'pay']);
}, [AuthMiddleware::class . '::authenticate']);

// Auction Status (Real-time)
$router->get('api/auction-status/(\d+)', [new AuctionStatusController(), 'getAuctionStatus']);
$router->get('api/auction-status/multiple', [new AuctionStatusController(), 'getMultipleAuctionStatus']);
$router->get('api/price-history/(\d+)', [new AuctionStatusController(), 'getPriceHistory']);

// --- Seller Role Specific Routes ---
$router->group('api/seller', function(Router $r) {
    $sellerController = new \App\Controllers\SellerController();
    $imageController = new \App\Controllers\ImageController();
    
    // Dashboard Stats
    $r->get('stats', [$sellerController, 'getStats']);
    
    // Inventory Management
    $r->get('listings', [$sellerController, 'getListings']);
    $r->put('items/(\d+)', [$sellerController, 'updateListing']);
    
    // Bulk Media Upload
    $r->post('items/(\d+)/images/bulk', [$imageController, 'bulkUpload']);
    
    // Messaging System
    $r->get('messages', [$sellerController, 'getMessages']);
    $r->get('messages/(\d+)', [$sellerController, 'getConversation']);
    $r->post('messages', [$sellerController, 'sendMessage']);
    
    // Shipping & Tracking
    $r->post('shipping/track', [$sellerController, 'updateShipping']);
    
    // Payout Requests
    $r->post('payouts', [$sellerController, 'requestPayout']);
}, [AuthMiddleware::class . '::authenticate']);

// Admin Routes
$router->group('api/admin', function(Router $r) {
    $adminController = new AdminController();
    
    // Most admin routes likely implement their own role check or should have RoleMiddleware
    // For now assuming existing controller handles specific role checks or we add middleware here
    
    $r->get('stats', [$adminController, 'getStatistics']);
    $r->get('users', [$adminController, 'getAllUsers']);
    
    $r->put('users/(\d+)/role', [$adminController, 'updateUserRole']);
    $r->post('users/(\d+)/suspend', [$adminController, 'suspendUser']);
    $r->post('users/(\d+)/ban', [$adminController, 'banUser']);
    $r->post('users/(\d+)/reactivate', [$adminController, 'reactivateUser']);
    
    $r->delete('items/(\d+)', [$adminController, 'deleteItem']);

    // Payout Management
    $r->get('payouts', [$adminController, 'getPayouts']);
    $r->put('payouts/(\d+)', [$adminController, 'updatePayoutStatus']);
}, [AuthMiddleware::class . '::authenticate']); // Basic Auth for all admin routes

// Dispatch
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = trim($uri, '/');
    
    // Parse JSON body
    $requestBody = null;
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $requestBody = json_decode($input, true);
        }
    }
    
    // Parse Query Params matches what Router expects ( $_GET can be used but passing it is explicit)
    $queryParams = $_GET;

    $router->dispatch($method, $uri, $requestBody, $queryParams);

} catch (\Exception $e) {
    AppLogger::logException($e);
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        Response::serverError($e->getMessage());
    } else {
        Response::serverError('An unexpected error occurred');
    }
}
