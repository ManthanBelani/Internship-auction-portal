-- Migration: Add category column to items table
ALTER TABLE items ADD COLUMN category TEXT;

-- Index for category searches
CREATE INDEX IF NOT EXISTS idx_items_category ON items(category);
