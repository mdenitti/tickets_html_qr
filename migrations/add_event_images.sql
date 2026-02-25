-- ============================================================
-- Migration: add_event_images
-- Created  : 2026-02-24
-- Purpose  : Adds a dedicated pivot table to store multiple
--            images per event.  Using a table (not a JSON
--            column) keeps things queryable, deletable and
--            extendable (e.g. alt-text, sort order).
-- Run once : safe to re-run thanks to IF NOT EXISTS
-- ============================================================

CREATE TABLE IF NOT EXISTS event_images (
    id          INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    event_id    BIGINT UNSIGNED  NOT NULL,
    filename    VARCHAR(255)     NOT NULL,
    path        VARCHAR(500)     NOT NULL,
    sort_order  INT UNSIGNED     NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    -- Remove images automatically when the parent event is deleted
    CONSTRAINT fk_event_images_event
        FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
