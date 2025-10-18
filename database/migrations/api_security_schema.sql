-- ============================================================================
-- API Security Schema
-- ============================================================================
-- Creates tables and views for API security features:
-- - API Keys management
-- - OAuth tokens
-- - API request logging
-- - API error tracking
-- 
-- Author: Multi-Menu Security Team
-- Version: 1.0.0
-- ============================================================================

-- ============================================================================
-- TABLE: api_keys
-- ============================================================================
-- Stores API keys for authentication
-- Keys are hashed with SHA-256 before storage

CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,                     -- Friendly name for the key
    key_hash TEXT NOT NULL UNIQUE,          -- SHA-256 hash of the key
    scopes TEXT DEFAULT '[]',               -- JSON array of allowed scopes
    rate_limit INTEGER DEFAULT NULL,        -- Custom rate limit (requests/min)
    is_active INTEGER DEFAULT 1,            -- 1 = active, 0 = revoked
    expires_at DATETIME DEFAULT NULL,       -- Expiration date (NULL = never)
    last_used_at DATETIME DEFAULT NULL,     -- Last usage timestamp
    request_count INTEGER DEFAULT 0,        -- Total requests made with this key
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME DEFAULT NULL,       -- Revocation timestamp
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_api_keys_user ON api_keys(user_id);
CREATE INDEX idx_api_keys_hash ON api_keys(key_hash);
CREATE INDEX idx_api_keys_active ON api_keys(is_active);
CREATE INDEX idx_api_keys_expires ON api_keys(expires_at);

-- ============================================================================
-- TABLE: oauth_tokens
-- ============================================================================
-- Stores OAuth2 access tokens

CREATE TABLE IF NOT EXISTS oauth_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    client_id TEXT NOT NULL,                -- OAuth client identifier
    access_token TEXT NOT NULL UNIQUE,      -- SHA-256 hash of access token
    refresh_token TEXT DEFAULT NULL,        -- SHA-256 hash of refresh token
    scopes TEXT DEFAULT '[]',               -- JSON array of granted scopes
    expires_at DATETIME NOT NULL,           -- Token expiration
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_oauth_tokens_user ON oauth_tokens(user_id);
CREATE INDEX idx_oauth_tokens_access ON oauth_tokens(access_token);
CREATE INDEX idx_oauth_tokens_expires ON oauth_tokens(expires_at);

-- ============================================================================
-- TABLE: api_requests
-- ============================================================================
-- Logs all API requests for monitoring and analytics

CREATE TABLE IF NOT EXISTS api_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER DEFAULT NULL,           -- User ID (if authenticated)
    auth_method TEXT DEFAULT NULL,          -- Authentication method used
    endpoint TEXT NOT NULL,                 -- API endpoint
    method TEXT NOT NULL,                   -- HTTP method (GET, POST, etc)
    ip_address TEXT NOT NULL,               -- Client IP address
    user_agent TEXT DEFAULT NULL,           -- User agent string
    request_data TEXT DEFAULT NULL,         -- JSON request data
    response_status INTEGER DEFAULT NULL,   -- HTTP response status
    response_time INTEGER DEFAULT NULL,     -- Response time in ms
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_api_requests_user ON api_requests(user_id);
CREATE INDEX idx_api_requests_endpoint ON api_requests(endpoint);
CREATE INDEX idx_api_requests_method ON api_requests(method);
CREATE INDEX idx_api_requests_ip ON api_requests(ip_address);
CREATE INDEX idx_api_requests_created ON api_requests(created_at);
CREATE INDEX idx_api_requests_status ON api_requests(response_status);

-- ============================================================================
-- TABLE: api_errors
-- ============================================================================
-- Logs API errors and security violations

CREATE TABLE IF NOT EXISTS api_errors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    endpoint TEXT NOT NULL,                 -- API endpoint
    method TEXT NOT NULL,                   -- HTTP method
    ip_address TEXT NOT NULL,               -- Client IP address
    user_agent TEXT DEFAULT NULL,           -- User agent string
    error_message TEXT NOT NULL,            -- Error message
    error_code INTEGER DEFAULT NULL,        -- HTTP error code
    request_data TEXT DEFAULT NULL,         -- JSON request data
    stack_trace TEXT DEFAULT NULL,          -- Error stack trace
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_api_errors_endpoint ON api_errors(endpoint);
CREATE INDEX idx_api_errors_ip ON api_errors(ip_address);
CREATE INDEX idx_api_errors_created ON api_errors(created_at);
CREATE INDEX idx_api_errors_code ON api_errors(error_code);

-- ============================================================================
-- VIEW: v_api_key_usage
-- ============================================================================
-- Shows API key usage statistics

CREATE VIEW IF NOT EXISTS v_api_key_usage AS
SELECT 
    k.id,
    k.user_id,
    u.username,
    k.name as key_name,
    k.scopes,
    k.rate_limit,
    k.is_active,
    k.expires_at,
    k.last_used_at,
    k.request_count,
    k.created_at,
    CASE 
        WHEN k.is_active = 0 THEN 'Revoked'
        WHEN k.expires_at IS NOT NULL AND k.expires_at < datetime('now') THEN 'Expired'
        ELSE 'Active'
    END as status,
    CASE
        WHEN k.last_used_at IS NOT NULL 
        THEN CAST((julianday('now') - julianday(k.last_used_at)) * 24 * 60 AS INTEGER)
        ELSE NULL
    END as minutes_since_last_use
FROM api_keys k
LEFT JOIN users u ON k.user_id = u.id
ORDER BY k.created_at DESC;

-- ============================================================================
-- VIEW: v_api_requests_by_endpoint
-- ============================================================================
-- Aggregates request counts by endpoint

CREATE VIEW IF NOT EXISTS v_api_requests_by_endpoint AS
SELECT 
    endpoint,
    method,
    COUNT(*) as total_requests,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT ip_address) as unique_ips,
    AVG(response_time) as avg_response_time,
    MIN(response_time) as min_response_time,
    MAX(response_time) as max_response_time,
    SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as success_count,
    SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as error_count,
    MIN(created_at) as first_request,
    MAX(created_at) as last_request
FROM api_requests
GROUP BY endpoint, method
ORDER BY total_requests DESC;

-- ============================================================================
-- VIEW: v_api_rate_limit_status
-- ============================================================================
-- Shows current rate limit status per IP/user (last 60 seconds)

CREATE VIEW IF NOT EXISTS v_api_rate_limit_status AS
SELECT 
    COALESCE(user_id, 0) as user_id,
    ip_address,
    COUNT(*) as requests_in_window,
    MAX(created_at) as last_request,
    CASE 
        WHEN COUNT(*) >= 100 THEN 'Limit Reached'
        WHEN COUNT(*) >= 80 THEN 'Warning'
        ELSE 'OK'
    END as status
FROM api_requests
WHERE created_at > datetime('now', '-60 seconds')
GROUP BY COALESCE(user_id, 0), ip_address
HAVING COUNT(*) >= 50  -- Only show if >= 50% of limit
ORDER BY requests_in_window DESC;

-- ============================================================================
-- VIEW: v_api_errors_summary
-- ============================================================================
-- Summarizes API errors by type and endpoint

CREATE VIEW IF NOT EXISTS v_api_errors_summary AS
SELECT 
    endpoint,
    method,
    error_code,
    COUNT(*) as error_count,
    COUNT(DISTINCT ip_address) as unique_ips,
    MIN(created_at) as first_error,
    MAX(created_at) as last_error,
    GROUP_CONCAT(DISTINCT SUBSTR(error_message, 1, 50)) as sample_messages
FROM api_errors
WHERE created_at > datetime('now', '-24 hours')
GROUP BY endpoint, method, error_code
ORDER BY error_count DESC;

-- ============================================================================
-- VIEW: v_suspicious_api_activity
-- ============================================================================
-- Detects suspicious API activity patterns

CREATE VIEW IF NOT EXISTS v_suspicious_api_activity AS
SELECT 
    ip_address,
    COUNT(*) as total_errors,
    COUNT(DISTINCT endpoint) as endpoints_hit,
    MIN(created_at) as first_error,
    MAX(created_at) as last_error,
    GROUP_CONCAT(DISTINCT error_code) as error_codes,
    CASE 
        WHEN COUNT(*) > 50 THEN 'High Risk'
        WHEN COUNT(*) > 20 THEN 'Medium Risk'
        ELSE 'Low Risk'
    END as threat_level
FROM api_errors
WHERE created_at > datetime('now', '-1 hour')
GROUP BY ip_address
HAVING COUNT(*) >= 10
ORDER BY total_errors DESC;

-- ============================================================================
-- VIEW: v_api_usage_by_user
-- ============================================================================
-- Shows API usage statistics per user

CREATE VIEW IF NOT EXISTS v_api_usage_by_user AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    COUNT(DISTINCT r.id) as total_requests,
    COUNT(DISTINCT DATE(r.created_at)) as active_days,
    COUNT(DISTINCT r.ip_address) as unique_ips,
    AVG(r.response_time) as avg_response_time,
    MIN(r.created_at) as first_request,
    MAX(r.created_at) as last_request,
    SUM(CASE WHEN r.response_status >= 400 THEN 1 ELSE 0 END) as error_count,
    ROUND(CAST(SUM(CASE WHEN r.response_status >= 400 THEN 1 ELSE 0 END) AS FLOAT) / COUNT(*) * 100, 2) as error_rate
FROM users u
LEFT JOIN api_requests r ON u.id = r.user_id
WHERE r.created_at > datetime('now', '-30 days')
GROUP BY u.id
ORDER BY total_requests DESC;

-- ============================================================================
-- TRIGGER: cleanup_old_api_requests
-- ============================================================================
-- Automatically removes API request logs older than 90 days

CREATE TRIGGER IF NOT EXISTS cleanup_old_api_requests
    AFTER INSERT ON api_requests
BEGIN
    DELETE FROM api_requests
    WHERE created_at < datetime('now', '-90 days');
END;

-- ============================================================================
-- TRIGGER: cleanup_old_api_errors
-- ============================================================================
-- Automatically removes API error logs older than 30 days

CREATE TRIGGER IF NOT EXISTS cleanup_old_api_errors
    AFTER INSERT ON api_errors
BEGIN
    DELETE FROM api_errors
    WHERE created_at < datetime('now', '-30 days');
END;

-- ============================================================================
-- TRIGGER: cleanup_expired_tokens
-- ============================================================================
-- Automatically removes expired OAuth tokens

CREATE TRIGGER IF NOT EXISTS cleanup_expired_tokens
    AFTER INSERT ON oauth_tokens
BEGIN
    DELETE FROM oauth_tokens
    WHERE expires_at < datetime('now');
END;

-- ============================================================================
-- UTILITY QUERIES
-- ============================================================================

-- Query 1: Get active API keys for a user
-- SELECT * FROM v_api_key_usage WHERE user_id = ? AND status = 'Active';

-- Query 2: Get most used endpoints
-- SELECT * FROM v_api_requests_by_endpoint LIMIT 10;

-- Query 3: Check rate limit status for IP
-- SELECT * FROM v_api_rate_limit_status WHERE ip_address = ?;

-- Query 4: Get recent errors
-- SELECT * FROM v_api_errors_summary LIMIT 20;

-- Query 5: Detect attacks
-- SELECT * FROM v_suspicious_api_activity;

-- Query 6: Get user API statistics
-- SELECT * FROM v_api_usage_by_user WHERE user_id = ?;

-- Query 7: Find slow endpoints
-- SELECT endpoint, method, avg_response_time 
-- FROM v_api_requests_by_endpoint 
-- WHERE avg_response_time > 1000 
-- ORDER BY avg_response_time DESC;

-- Query 8: Get failed authentication attempts by IP
-- SELECT ip_address, COUNT(*) as attempts, MAX(created_at) as last_attempt
-- FROM api_errors
-- WHERE error_code = 401 
-- AND created_at > datetime('now', '-1 hour')
-- GROUP BY ip_address
-- HAVING COUNT(*) > 5
-- ORDER BY attempts DESC;

-- Query 9: Check if API key is valid
-- SELECT id, user_id, name, scopes 
-- FROM api_keys 
-- WHERE key_hash = ? 
-- AND is_active = 1 
-- AND (expires_at IS NULL OR expires_at > datetime('now'));

-- Query 10: Revoke all keys for a user
-- UPDATE api_keys 
-- SET is_active = 0, revoked_at = datetime('now')
-- WHERE user_id = ?;

-- ============================================================================
-- SAMPLE DATA (for testing only - remove in production)
-- ============================================================================

-- Insert sample API key for user 1 (admin)
-- INSERT INTO api_keys (user_id, name, key_hash, scopes, rate_limit)
-- VALUES (
--     1, 
--     'Admin API Key', 
--     '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', -- hash of 'password'
--     '["read", "write", "admin"]',
--     200
-- );

-- Insert sample OAuth token
-- INSERT INTO oauth_tokens (user_id, client_id, access_token, scopes, expires_at)
-- VALUES (
--     1,
--     'web-client',
--     '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8',
--     '["read", "write"]',
--     datetime('now', '+1 hour')
-- );
