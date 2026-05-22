-- =============================================================
-- VantageMarket — Auth Token Tables (add to vantagemarket_schema.sql)
-- =============================================================

USE vantagemarket;

-- =============================================================
-- TABLE: Remember_Tokens
-- UC04: Persistent "Remember Me" login tokens
-- Token stored as SHA-256 hash — raw token only goes in cookie
-- =============================================================

CREATE TABLE IF NOT EXISTS Remember_Tokens (
    token_id    INT             NOT NULL AUTO_INCREMENT,
    user_id     INT             NOT NULL,
    token_hash  VARCHAR(64)     NOT NULL,
    expires_at  DATETIME        NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (token_id),
    UNIQUE KEY uq_remember_user (user_id),       -- one token per user
    UNIQUE KEY uq_remember_hash (token_hash),
    CONSTRAINT fk_remember_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_remember_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Password_Reset_Tokens
-- UC04: Forgot-password flow — single-use, time-limited
-- =============================================================

CREATE TABLE IF NOT EXISTS Password_Reset_Tokens (
    token_id    INT             NOT NULL AUTO_INCREMENT,
    user_id     INT             NOT NULL,
    token_hash  VARCHAR(64)     NOT NULL,
    expires_at  DATETIME        NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (token_id),
    UNIQUE KEY uq_reset_user (user_id),          -- one pending reset per user
    UNIQUE KEY uq_reset_hash (token_hash),
    CONSTRAINT fk_reset_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
