-- Migration: Add reserve price and commission columns to items table
ALTER TABLE items 
ADD COLUMN IF NOT EXISTS reserve_price DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5, 4) DEFAULT 0.05,
ADD COLUMN IF NOT EXISTS reserve_met BOOLEAN DEFAULT FALSE;
