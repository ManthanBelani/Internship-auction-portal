-- Migration: Add shipping and tracking columns to transactions table
ALTER TABLE transactions
ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(255),
ADD COLUMN IF NOT EXISTS shipping_status VARCHAR(50) DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS payout_status VARCHAR(50) DEFAULT 'pending';
