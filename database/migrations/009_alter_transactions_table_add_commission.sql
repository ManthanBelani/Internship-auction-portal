-- Migration: Add commission tracking columns to transactions table
ALTER TABLE transactions
ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS seller_payout DECIMAL(10, 2) NOT NULL DEFAULT 0.00;
