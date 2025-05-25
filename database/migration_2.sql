-- Migration name: migration_2.sql
-- Migration handler: add_is_deleted_on_expenses_table
ALTER TABLE expenses
    ADD COLUMN is_deleted INTEGER NOT NULL DEFAULT 0;

ALTER TABLE expenses
    ADD COLUMN deleted_at TEXT DEFAULT NULL;