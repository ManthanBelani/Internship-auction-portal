<?php

namespace App\Services;

use App\Config\Database;
use App\Utils\AppLogger;

/**
 * Email Service for BidOrbit
 * 
 * Handles email notifications for auction events, 
 * user registration, password reset, etc.
 */
class EmailService
{
    private $db;
    private $mailHost;
    private $mailPort;
    private $mailUsername;
    private $mailPassword;
    private $mailFromAddress;
    private $mailFromName;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->mailHost = $_ENV['MAIL_HOST'] ?? 'smtp.mailgun.org';
        $this->mailPort = (int)($_ENV['MAIL_PORT'] ?? 587);
        $this->mailUsername = $_ENV['MAIL_USERNAME'] ?? '';
        $this->mailPassword = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->mailFromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bidorbit.com';
        $this->mailFromName = $_ENV['MAIL_FROM_NAME'] ?? 'BidOrbit';
    }

    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            // In production, use actual SMTP
            if ($_ENV['APP_ENV'] === 'production' && $this->mailUsername) {
                return $this->sendViaSmtp($to, $subject, $body, $options);
            }
            
            // Development: Log email instead of sending
            AppLogger::info('Email (Development Mode)', [
                'to' => $to,
                'subject' => $subject,
                'body_preview' => substr($body, 0, 200),
            ]);
            
            // Store in database for testing
            $this->storeEmail($to, $subject, $body);
            
            return true;
        } catch (\Exception $e) {
            AppLogger::error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email via SMTP (production)
     */
    private function sendViaSmtp(string $to, string $subject, string $body, array $options = []): bool
    {
        $headers = [
            'From' => "{$this->mailFromName} <{$this->mailFromAddress}>",
            'To' => $to,
            'Subject' => $subject,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
        
        // Add reply-to if specified
        if (isset($options['replyTo'])) {
            $headers['Reply-To'] = $options['replyTo'];
        }
        
        // Use PHP's mail function or a library like PHPMailer
        // For production, consider using a service like SendGrid, Mailgun, or Amazon SES
        
        $headerStr = '';
        foreach ($headers as $key => $value) {
            $headerStr .= "$key: $value\r\n";
        }
        
        return mail($to, $subject, $body, $headerStr);
    }

    /**
     * Store email in database (for development/testing)
     */
    private function storeEmail(string $to, string $subject, string $body): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO email_log (recipient, subject, body, created_at)
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$to, $subject, $body]);
        } catch (\Exception $e) {
            AppLogger::error('Failed to store email', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(string $email, string $name): bool
    {
        $subject = 'Welcome to BidOrbit!';
        $body = $this->renderTemplate('welcome', [
            'name' => $name,
            'loginUrl' => ($_ENV['APP_URL'] ?? 'http://localhost:8000') . '/login',
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send bid notification to seller
     */
    public function sendBidNotificationSeller(string $email, array $data): bool
    {
        $subject = "New bid on your item: {$data['itemTitle']}";
        $body = $this->renderTemplate('bid_notification_seller', [
            'itemName' => $data['itemTitle'],
            'bidAmount' => number_format($data['bidAmount'], 2),
            'bidderName' => $data['bidderName'],
            'itemUrl' => $this->getItemUrl($data['itemId']),
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send outbid notification
     */
    public function sendOutbidNotification(string $email, array $data): bool
    {
        $subject = "You've been outbid on {$data['itemTitle']}!";
        $body = $this->renderTemplate('outbid_notification', [
            'itemName' => $data['itemTitle'],
            'yourBid' => number_format($data['previousBid'], 2),
            'newBid' => number_format($data['newBid'], 2),
            'itemUrl' => $this->getItemUrl($data['itemId']),
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send auction won notification
     */
    public function sendAuctionWonNotification(string $email, array $data): bool
    {
        $subject = "Congratulations! You won: {$data['itemTitle']}";
        $body = $this->renderTemplate('auction_won', [
            'itemName' => $data['itemTitle'],
            'winningBid' => number_format($data['winningBid'], 2),
            'itemUrl' => $this->getItemUrl($data['itemId']),
            'checkoutUrl' => $this->getCheckoutUrl($data['itemId']),
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send auction ended notification to seller
     */
    public function sendAuctionEndedSellerNotification(string $email, array $data): bool
    {
        $subject = "Your auction has ended: {$data['itemTitle']}";
        $body = $this->renderTemplate('auction_ended_seller', [
            'itemName' => $data['itemTitle'],
            'finalPrice' => number_format($data['finalPrice'], 2),
            'winnerName' => $data['winnerName'] ?? 'No winner',
            'sold' => $data['sold'],
            'itemUrl' => $this->getItemUrl($data['itemId']),
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send auction ending soon notification
     */
    public function sendAuctionEndingSoonNotification(string $email, array $data): bool
    {
        $subject = "Auction ending soon: {$data['itemTitle']}";
        $body = $this->renderTemplate('auction_ending_soon', [
            'itemName' => $data['itemTitle'],
            'currentPrice' => number_format($data['currentPrice'], 2),
            'timeRemaining' => $data['timeRemaining'],
            'itemUrl' => $this->getItemUrl($data['itemId']),
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $email, string $resetToken): bool
    {
        $resetUrl = ($_ENV['APP_URL'] ?? 'http://localhost:8000') . "/reset-password?token=$resetToken";
        
        $subject = 'Reset Your BidOrbit Password';
        $body = $this->renderTemplate('password_reset', [
            'resetUrl' => $resetUrl,
            'expiresIn' => '1 hour',
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation(string $email, array $orderData): bool
    {
        $subject = "Order Confirmation - {$orderData['itemTitle']}";
        $body = $this->renderTemplate('order_confirmation', [
            'orderNumber' => $orderData['orderNumber'],
            'itemName' => $orderData['itemTitle'],
            'total' => number_format($orderData['total'], 2),
            'shippingAddress' => $orderData['shippingAddress'],
            'estimatedDelivery' => $orderData['estimatedDelivery'],
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send shipping notification
     */
    public function sendShippingNotification(string $email, array $data): bool
    {
        $subject = "Your item has been shipped: {$data['itemTitle']}";
        $body = $this->renderTemplate('shipping_notification', [
            'itemName' => $data['itemTitle'],
            'trackingNumber' => $data['trackingNumber'],
            'carrier' => $data['carrier'] ?? 'Standard Shipping',
            'trackingUrl' => $data['trackingUrl'] ?? null,
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Send payout notification to seller
     */
    public function sendPayoutNotification(string $email, array $data): bool
    {
        $subject = "Payout processed: \${$data['amount']}";
        $body = $this->renderTemplate('payout_notification', [
            'amount' => number_format($data['amount'], 2),
            'method' => $data['method'],
            'payoutId' => $data['payoutId'],
            'processingTime' => '3-5 business days',
        ]);
        
        return $this->send($email, $subject, $body);
    }

    /**
     * Render email template
     */
    private function renderTemplate(string $template, array $data): string
    {
        // Base template with header and footer
        $html = $this->getEmailHeader();
        
        switch ($template) {
            case 'welcome':
                $html .= $this->getWelcomeTemplate($data);
                break;
            case 'bid_notification_seller':
                $html .= $this->getBidNotificationSellerTemplate($data);
                break;
            case 'outbid_notification':
                $html .= $this->getOutbidTemplate($data);
                break;
            case 'auction_won':
                $html .= $this->getAuctionWonTemplate($data);
                break;
            case 'auction_ended_seller':
                $html .= $this->getAuctionEndedSellerTemplate($data);
                break;
            case 'auction_ending_soon':
                $html .= $this->getAuctionEndingSoonTemplate($data);
                break;
            case 'password_reset':
                $html .= $this->getPasswordResetTemplate($data);
                break;
            case 'order_confirmation':
                $html .= $this->getOrderConfirmationTemplate($data);
                break;
            case 'shipping_notification':
                $html .= $this->getShippingNotificationTemplate($data);
                break;
            case 'payout_notification':
                $html .= $this->getPayoutNotificationTemplate($data);
                break;
            default:
                $html .= '<p>' . htmlspecialchars(json_encode($data)) . '</p>';
        }
        
        $html .= $this->getEmailFooter();
        
        return $html;
    }

    /**
     * Get email header
     */
    private function getEmailHeader(): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="padding: 20px;">
                        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <tr>
                                <td style="background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 30px; text-align: center;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 28px;">
                                        🔨 BidOrbit
                                    </h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 30px;">
        ';
    }

    /**
     * Get email footer
     */
    private function getEmailFooter(): string
    {
        $year = date('Y');
        return '
                                </td>
                            </tr>
                            <tr>
                                <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                                    <p style="margin: 0; color: #64748b; font-size: 14px;">
                                        © ' . $year . ' BidOrbit. All rights reserved.<br>
                                        <a href="#" style="color: #3b82f6;">Unsubscribe</a> | 
                                        <a href="#" style="color: #3b82f6;">Privacy Policy</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }

    /**
     * Get welcome email template
     */
    private function getWelcomeTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">Welcome to BidOrbit, ' . htmlspecialchars($data['name']) . '!</h2>
            <p style="margin: 0 0 20px; color: #475569; line-height: 1.6;">
                Thank you for joining BidOrbit, your premier auction marketplace. 
                You can now start bidding on amazing items or list your own auctions!
            </p>
            <p style="margin: 0 0 30px;">
                <a href="' . htmlspecialchars($data['loginUrl']) . '" style="display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                    Start Exploring
                </a>
            </p>
        ';
    }

    /**
     * Get outbid notification template
     */
    private function getOutbidTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">You\'ve been outbid!</h2>
            <p style="margin: 0 0 20px; color: #475569; line-height: 1.6;">
                Someone has placed a higher bid on <strong>' . htmlspecialchars($data['itemName']) . '</strong>.
            </p>
            <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; color: #64748b;">Your bid:</td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-align: right;">$' . $data['yourBid'] . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; color: #64748b;">New bid:</td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #ef4444;">$' . $data['newBid'] . '</td>
                </tr>
            </table>
            <p style="margin: 0 0 30px;">
                <a href="' . htmlspecialchars($data['itemUrl']) . '" style="display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                    Place a Higher Bid
                </a>
            </p>
        ';
    }

    /**
     * Get auction won template
     */
    private function getAuctionWonTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">🎉 Congratulations! You Won!</h2>
            <p style="margin: 0 0 20px; color: #475569; line-height: 1.6;">
                You have won the auction for <strong>' . htmlspecialchars($data['itemName']) . '</strong>!
            </p>
            <div style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
                <p style="margin: 0; color: #166534; font-size: 24px; font-weight: bold;">
                    Winning Bid: $' . $data['winningBid'] . '
                </p>
            </div>
            <p style="margin: 0 0 30px;">
                <a href="' . htmlspecialchars($data['checkoutUrl']) . '" style="display: inline-block; padding: 12px 24px; background-color: #22c55e; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                    Complete Your Purchase
                </a>
            </p>
        ';
    }

    /**
     * Get password reset template
     */
    private function getPasswordResetTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">Reset Your Password</h2>
            <p style="margin: 0 0 20px; color: #475569; line-height: 1.6;">
                We received a request to reset your password. Click the button below to create a new password.
            </p>
            <p style="margin: 0 0 20px;">
                <a href="' . htmlspecialchars($data['resetUrl']) . '" style="display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                    Reset Password
                </a>
            </p>
            <p style="margin: 0 0 20px; color: #64748b; font-size: 14px;">
                This link will expire in ' . htmlspecialchars($data['expiresIn']) . '. If you did not request a password reset, please ignore this email.
            </p>
        ';
    }

    /**
     * Get item URL
     */
    private function getItemUrl(int $itemId): string
    {
        return ($_ENV['APP_URL'] ?? 'http://localhost:8000') . "/items/$itemId";
    }

    /**
     * Get checkout URL
     */
    private function getCheckoutUrl(int $itemId): string
    {
        return ($_ENV['APP_URL'] ?? 'http://localhost:8000') . "/checkout/$itemId";
    }

    // Additional template methods...
    private function getBidNotificationSellerTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">New Bid on Your Item!</h2>
            <p style="margin: 0 0 20px; color: #475569;">
                <strong>' . htmlspecialchars($data['bidderName']) . '</strong> placed a bid of <strong>$' . $data['bidAmount'] . '</strong> on <strong>' . htmlspecialchars($data['itemName']) . '</strong>
            </p>
            <p><a href="' . htmlspecialchars($data['itemUrl']) . '" style="color: #3b82f6;">View Item</a></p>
        ';
    }

    private function getAuctionEndedSellerTemplate(array $data): string
    {
        $statusText = $data['sold'] ? 'Your item was sold!' : 'Your item did not sell.';
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">Auction Ended</h2>
            <p style="margin: 0 0 20px; color: #475569;">' . $statusText . '</p>
            <p style="margin: 0 0 20px; color: #475569;">
                Item: <strong>' . htmlspecialchars($data['itemName']) . '</strong><br>
                Final Price: <strong>$' . $data['finalPrice'] . '</strong>
            </p>
        ';
    }

    private function getAuctionEndingSoonTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">⏰ Auction Ending Soon!</h2>
            <p style="margin: 0 0 20px; color: #475569;">
                <strong>' . htmlspecialchars($data['itemName']) . '</strong> ends in ' . htmlspecialchars($data['timeRemaining']) . '!
            </p>
            <p style="margin: 0 0 20px; color: #475569;">Current Price: <strong>$' . $data['currentPrice'] . '</strong></p>
            <p><a href="' . htmlspecialchars($data['itemUrl']) . '" style="color: #3b82f6;">View Auction</a></p>
        ';
    }

    private function getOrderConfirmationTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">Order Confirmed!</h2>
            <p style="margin: 0 0 20px; color: #475569;">Order #' . htmlspecialchars($data['orderNumber']) . '</p>
            <p style="margin: 0 0 20px; color: #475569;">
                Item: <strong>' . htmlspecialchars($data['itemName']) . '</strong><br>
                Total: <strong>$' . $data['total'] . '</strong>
            </p>
        ';
    }

    private function getShippingNotificationTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">📦 Your Item Has Shipped!</h2>
            <p style="margin: 0 0 20px; color: #475569;">
                <strong>' . htmlspecialchars($data['itemName']) . '</strong> is on its way!
            </p>
            <p style="margin: 0 0 20px; color: #475569;">
                Tracking Number: <strong>' . htmlspecialchars($data['trackingNumber']) . '</strong>
            </p>
        ';
    }

    private function getPayoutNotificationTemplate(array $data): string
    {
        return '
            <h2 style="margin: 0 0 20px; color: #1e293b;">💰 Payout Processed!</h2>
            <p style="margin: 0 0 20px; color: #475569;">
                A payout of <strong>$' . $data['amount'] . '</strong> has been processed to your ' . htmlspecialchars($data['method']) . ' account.
            </p>
            <p style="margin: 0 0 20px; color: #64748b; font-size: 14px;">
                Expected arrival: ' . htmlspecialchars($data['processingTime']) . '
            </p>
        ';
    }
}
