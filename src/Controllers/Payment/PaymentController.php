<?php

namespace App\Controllers\Payment;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Order;
use App\Services\Payment\StripeService;
use App\Validators\Validator;

class PaymentController
{
    private Response $response;
    private StripeService $stripe;

    public function __construct()
    {
        $this->response = new Response();
        $this->stripe = new StripeService();
    }

    /**
     * Create payment intent
     */
    public function createIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'currency' => 'string|max:3'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $orderId = $request->input('order_id');
            $order = Order::find($orderId);

            if (!$order) {
                return $this->response->error('Order not found', 404);
            }

            // Check if order already has a payment intent
            $existingPayment = Database::queryOne(
                "SELECT * FROM payments WHERE order_id = :order_id AND status != 'failed' LIMIT 1",
                ['order_id' => $orderId]
            );

            if ($existingPayment) {
                return $this->response->error('Payment already initiated for this order', 422);
            }

            $currency = $request->input('currency', 'usd');
            $amount = $order['total_amount'];

            // Create Stripe payment intent
            $intent = $this->stripe->createPaymentIntent($amount, $currency, [
                'order_id' => $orderId,
                'order_number' => $order['order_number'],
                'tenant_id' => $order['tenant_id']
            ]);

            // Save payment record
            $paymentId = Database::insert('payments', [
                'tenant_id' => $order['tenant_id'],
                'order_id' => $orderId,
                'payment_method' => 'stripe',
                'transaction_id' => $intent['payment_intent_id'],
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'status' => 'pending',
                'metadata' => json_encode($intent),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return $this->response->json([
                'payment_id' => $paymentId,
                'client_secret' => $intent['client_secret'],
                'amount' => $amount,
                'currency' => $currency
            ], 201, 'Payment intent created successfully');

        } catch (\Exception $e) {
            logError('Failed to create payment intent: ' . $e->getMessage());
            return $this->response->error('Failed to create payment intent', 500);
        }
    }

    /**
     * Confirm payment
     */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $paymentIntentId = $request->input('payment_intent_id');

            // Retrieve payment intent from Stripe
            $intent = $this->stripe->getPaymentIntent($paymentIntentId);

            if (!$intent) {
                return $this->response->error('Payment intent not found', 404);
            }

            // Find payment record
            $payment = Database::queryOne(
                "SELECT * FROM payments WHERE transaction_id = :transaction_id",
                ['transaction_id' => $paymentIntentId]
            );

            if (!$payment) {
                return $this->response->error('Payment record not found', 404);
            }

            // Update payment status based on Stripe status
            $status = $this->mapStripeStatus($intent['status']);

            Database::update('payments', [
                'status' => $status,
                'paid_at' => $status === 'completed' ? now() : null,
                'updated_at' => now()
            ], 'id = :id', ['id' => $payment['id']]);

            // Update order payment status if completed
            if ($status === 'completed') {
                Order::update($payment['order_id'], [
                    'payment_status' => 'completed',
                    'status' => 'confirmed' // Auto-confirm order on payment
                ]);
            }

            return $this->response->json([
                'payment_id' => $payment['id'],
                'status' => $status,
                'amount' => $intent['amount']
            ], 200, 'Payment confirmed');

        } catch (\Exception $e) {
            logError('Payment confirmation failed: ' . $e->getMessage());
            return $this->response->error('Payment confirmation failed', 500);
        }
    }

    /**
     * Get payment details
     */
    public function show(Request $request)
    {
        $orderId = $request->param('order_id');

        try {
            $payment = Database::queryOne(
                "SELECT * FROM payments WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1",
                ['order_id' => $orderId]
            );

            if (!$payment) {
                return $this->response->error('Payment not found', 404);
            }

            // Remove sensitive data
            unset($payment['metadata']);

            return $this->response->json($payment, 200);

        } catch (\Exception $e) {
            logError('Failed to fetch payment: ' . $e->getMessage());
            return $this->response->error('Failed to fetch payment', 500);
        }
    }

    /**
     * Stripe webhook handler
     */
    public function webhook(Request $request)
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = $this->stripe->verifyWebhook($payload, $signature);

            if (!$event) {
                return $this->response->error('Invalid webhook signature', 400);
            }

            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentCanceled($event->data->object);
                    break;

                default:
                    logInfo('Unhandled webhook event: ' . $event->type);
            }

            return $this->response->json(['received' => true], 200);

        } catch (\Exception $e) {
            logError('Webhook processing failed: ' . $e->getMessage());
            return $this->response->error('Webhook processing failed', 500);
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess(object $paymentIntent): void
    {
        $payment = Database::queryOne(
            "SELECT * FROM payments WHERE transaction_id = :transaction_id",
            ['transaction_id' => $paymentIntent->id]
        );

        if ($payment) {
            // Update payment status
            Database::update('payments', [
                'status' => 'completed',
                'paid_at' => now(),
                'updated_at' => now()
            ], 'id = :id', ['id' => $payment['id']]);

            // Update order
            Order::update($payment['order_id'], [
                'payment_status' => 'completed',
                'status' => 'confirmed'
            ]);

            logInfo('Payment succeeded for order: ' . $payment['order_id']);

            // TODO: Send email confirmation
            // TODO: Send SMS notification
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed(object $paymentIntent): void
    {
        $payment = Database::queryOne(
            "SELECT * FROM payments WHERE transaction_id = :transaction_id",
            ['transaction_id' => $paymentIntent->id]
        );

        if ($payment) {
            // Update payment status
            Database::update('payments', [
                'status' => 'failed',
                'updated_at' => now()
            ], 'id = :id', ['id' => $payment['id']]);

            // Update order
            Order::update($payment['order_id'], [
                'payment_status' => 'failed'
            ]);

            logInfo('Payment failed for order: ' . $payment['order_id']);
        }
    }

    /**
     * Handle canceled payment
     */
    private function handlePaymentCanceled(object $paymentIntent): void
    {
        $payment = Database::queryOne(
            "SELECT * FROM payments WHERE transaction_id = :transaction_id",
            ['transaction_id' => $paymentIntent->id]
        );

        if ($payment) {
            // Update payment status
            Database::update('payments', [
                'status' => 'cancelled',
                'updated_at' => now()
            ], 'id = :id', ['id' => $payment['id']]);

            logInfo('Payment canceled for order: ' . $payment['order_id']);
        }
    }

    /**
     * Map Stripe status to our status
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        $statusMap = [
            'requires_payment_method' => 'pending',
            'requires_confirmation' => 'pending',
            'requires_action' => 'processing',
            'processing' => 'processing',
            'succeeded' => 'completed',
            'canceled' => 'cancelled',
            'requires_capture' => 'processing'
        ];

        return $statusMap[$stripeStatus] ?? 'failed';
    }

    /**
     * Refund payment
     */
    public function refund(Request $request)
    {
        $orderId = $request->param('order_id');

        $validator = Validator::make($request->all(), [
            'amount' => 'numeric',
            'reason' => 'max:500'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $payment = Database::queryOne(
                "SELECT * FROM payments WHERE order_id = :order_id AND status = 'completed' LIMIT 1",
                ['order_id' => $orderId]
            );

            if (!$payment) {
                return $this->response->error('No completed payment found for this order', 404);
            }

            $amount = $request->input('amount', null);

            // Process refund with Stripe
            $refund = $this->stripe->refundPayment($payment['transaction_id'], $amount);

            // Update payment status
            Database::update('payments', [
                'status' => 'refunded',
                'refunded_at' => now(),
                'updated_at' => now()
            ], 'id = :id', ['id' => $payment['id']]);

            // Update order
            Order::update($orderId, [
                'payment_status' => 'refunded',
                'status' => 'refunded'
            ]);

            return $this->response->json([
                'refund_id' => $refund['id'],
                'amount' => $refund['amount'],
                'status' => $refund['status']
            ], 200, 'Payment refunded successfully');

        } catch (\Exception $e) {
            logError('Refund failed: ' . $e->getMessage());
            return $this->response->error('Refund failed', 500);
        }
    }
}
