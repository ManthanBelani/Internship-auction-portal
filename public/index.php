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

// Global Exception Handler
set_exception_handler(function ($e) {
    AppLogger::logException($e);
    $statusCode = 500;
    if ($e->getCode() >= 400 && $e->getCode() < 600) {
        $statusCode = $e->getCode();
    }
    
    Response::error(
        'UNEXPECTED_ERROR',
        ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? $e->getMessage() : 'An internal server error occurred',
        $statusCode,
        ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? ['trace' => $e->getTraceAsString()] : null
    );
});

// Global Error Handler
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

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
use App\Controllers\PaymentController;
use App\Controllers\ShippingController;
use App\Controllers\OrderController;

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
        'technology' => 'PHP + MySQL'
    ]);
});

// User Routes
$router->post('api/users/register', [new UserController(), 'register']);
$router->post('api/users/login', [new UserController(), 'login']);
$router->post('api/users/refresh', [new UserController(), 'refreshToken']);

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

// Payment Routes
$router->group('api/payments', function(Router $r) {
    $paymentController = new \App\Controllers\PaymentController();
    
    $r->post('create-intent', [$paymentController, 'createPaymentIntent']);
    $r->post('confirm', [$paymentController, 'confirmPayment']);
    $r->get('methods', [$paymentController, 'getPaymentMethods']);
    $r->post('methods', [$paymentController, 'addPaymentMethod']);
    $r->delete('methods/(\d+)', [$paymentController, 'deletePaymentMethod']);
    $r->get('history', [$paymentController, 'getPaymentHistory']);
}, [AuthMiddleware::class . '::authenticate']);

// Shipping Routes
$router->group('api/shipping', function(Router $r) {
    $shippingController = new \App\Controllers\ShippingController();
    
    $r->get('addresses', [$shippingController, 'getAddresses']);
    $r->post('addresses', [$shippingController, 'addAddress']);
    $r->put('addresses/(\d+)', [$shippingController, 'updateAddress']);
    $r->delete('addresses/(\d+)', [$shippingController, 'deleteAddress']);
    $r->post('calculate', [$shippingController, 'calculateShipping']);
}, [AuthMiddleware::class . '::authenticate']);

// Order Routes
$router->group('api/orders', function(Router $r) {
    $orderController = new \App\Controllers\OrderController();
    
    $r->get('', [$orderController, 'getOrders']);
    $r->get('(\d+)', [$orderController, 'getOrderById']);
    $r->post('create', [$orderController, 'createOrder']);
    $r->put('(\d+)/status', [$orderController, 'updateOrderStatus']);
    $r->post('(\d+)/cancel', [$orderController, 'cancelOrder']);
    $r->get('won-items', [$orderController, 'getWonItems']);
}, [AuthMiddleware::class . '::authenticate']);

// Auction Status (Real-time)
$router->get('api/auction-status/(\d+)', [new AuctionStatusController(), 'getAuctionStatus']);
$router->get('api/auction-status/multiple', [new AuctionStatusController(), 'getMultipleAuctionStatus']);
$router->get('api/price-history/(\d+)', [new AuctionStatusController(), 'getPriceHistory']);

// --- Seller Role Specific Routes ---
$router->group('api/seller', function(Router $r) {
    $sellerController = new \App\Controllers\SellerController();
    $salesController = new \App\Controllers\SalesController();
    $analyticsController = new \App\Controllers\AnalyticsController();
    $imageController = new \App\Controllers\ImageController();
    
    // Dashboard Stats
    $r->get('stats', [$sellerController, 'getStats']);
    
    // Inventory Management
    $r->get('listings', [$sellerController, 'getListings']);
    $r->put('items/(\d+)', [$sellerController, 'updateListing']);
    
    // Sales Management
    $r->get('sales', [$salesController, 'getSales']);
    $r->get('sales/(\d+)', [$salesController, 'getSaleDetails']);
    $r->put('sales/(\d+)/ship', [$salesController, 'markAsShipped']);
    $r->put('sales/(\d+)/deliver', [$salesController, 'markAsDelivered']);
    $r->get('revenue', [$salesController, 'getRevenue']);
    
    // Analytics
    $r->get('analytics/overview', [$analyticsController, 'getOverview']);
    $r->get('analytics/revenue', [$analyticsController, 'getRevenueAnalytics']);
    $r->get('analytics/performance', [$analyticsController, 'getPerformance']);
    $r->get('analytics/categories', [$analyticsController, 'getCategoryAnalytics']);
    
    // Bulk Media Upload
    $r->post('items/(\d+)/images/bulk', [$imageController, 'bulkUpload']);
    
    // Messaging System
    $r->get('messages', [$sellerController, 'getMessages']);
    $r->get('messages/(\d+)', [$sellerController, 'getConversation']);
    $r->post('messages', [$sellerController, 'sendMessage']);
    $r->post('messages/send', [$sellerController, 'sendMessageByConversation']);
    $r->put('messages/(\d+)/read', [$sellerController, 'markMessageAsRead']);
    
    // Shipping & Tracking
    $r->post('shipping/track', [$sellerController, 'updateShipping']);
    
    // Payout Requests
    $r->get('payouts', [$sellerController, 'getPayouts']);
    $r->post('payouts', [$sellerController, 'requestPayout']);
    $r->post('payouts/request', [$sellerController, 'requestPayout']);
    $r->get('balance', [$sellerController, 'getBalance']);
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
    
    // Transaction Management
    $r->get('transactions', [$adminController, 'getTransactions']);
    
    // Review Management
    $r->get('reviews', [$adminController, 'getReviews']);
    $r->delete('reviews/(\d+)', [$adminController, 'deleteReview']);
    
    // Earnings
    $r->get('earnings', [$adminController, 'getEarnings']);
}, [AuthMiddleware::class . '::authenticate']); // Basic Auth for all admin routes

// Dispatch
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
