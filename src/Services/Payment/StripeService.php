<?php

namespace App\Services\Payment;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class StripeService
{
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey = env('STRIPE_SECRET_KEY');
        $this->webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', array $metadata = []): array
    {
        try {
            // Amount should be in cents
            $amountInCents = (int) ($amount * 100);

            $intent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => $intent->status
            ];

        } catch (\Exception $e) {
            logError('Stripe payment intent creation failed: ' . $e->getMessage());
            throw new \Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve payment intent
     */
    public function getPaymentIntent(string $paymentIntentId): ?array
    {
        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'id' => $intent->id,
                'amount' => $intent->amount / 100, // Convert from cents
                'currency' => $intent->currency,
                'status' => $intent->status,
                'metadata' => $intent->metadata->toArray(),
                'created' => $intent->created
            ];

        } catch (\Exception $e) {
            logError('Failed to retrieve payment intent: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Confirm payment intent
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);
            $intent->confirm();

            return [
                'id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount / 100
            ];

        } catch (\Exception $e) {
            logError('Payment confirmation failed: ' . $e->getMessage());
            throw new \Exception('Payment confirmation failed: ' . $e->getMessage());
        }
    }

    /**
     * Cancel payment intent
     */
    public function cancelPayment(string $paymentIntentId): bool
    {
        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);

            if ($intent->status === 'requires_payment_method' || $intent->status === 'requires_confirmation') {
                $intent->cancel();
                return true;
            }

            return false;

        } catch (\Exception $e) {
            logError('Payment cancellation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify webhook signature and parse event
     */
    public function verifyWebhook(string $payload, string $signature): ?object
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $this->webhookSecret
            );

            return $event;

        } catch (\UnexpectedValueException $e) {
            logError('Invalid webhook payload: ' . $e->getMessage());
            return null;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            logError('Invalid webhook signature: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null): array
    {
        try {
            $refundData = ['payment_intent' => $paymentIntentId];

            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100); // Convert to cents
            }

            $refund = \Stripe\Refund::create($refundData);

            return [
                'id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
                'reason' => $refund->reason
            ];

        } catch (\Exception $e) {
            logError('Refund failed: ' . $e->getMessage());
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }
}
