ALTER TABLE bookings
    ADD COLUMN deleted_at DATETIME DEFAULT NULL,
    ADD COLUMN deleted_slot_was_booked TINYINT(1) DEFAULT NULL,
    ADD INDEX idx_bookings_deleted_at (deleted_at);
