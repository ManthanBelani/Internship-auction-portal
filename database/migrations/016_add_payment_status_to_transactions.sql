-- Migration: Add payment_status to transactions table
ALTER TABLE transactions
ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'unpaid'; -- unpaid, paid, failed, refunded
