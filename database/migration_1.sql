-- Migration name: migration_1.sql
-- Migration handler: update_amountCents_on_expenses_table

--       An idea would be to get a new table column that gets mapped with the values in
--       amount_cents, then to drop the column and rename the new column to amount_cents.
    ALTER TABLE expenses
        ADD COLUMN amount REAL NOT NULL DEFAULT 0;

    UPDATE expenses
        SET amount = amount_cents / 100.0;

    ALTER TABLE expenses
        DROP column amount_cents;

    ALTER TABLE expenses
        RENAME COLUMN amount TO amount_cents;
