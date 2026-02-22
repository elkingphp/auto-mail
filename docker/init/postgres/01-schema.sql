-- Unified Testing Schema for PostgreSQL
CREATE TABLE IF NOT EXISTS operational_data (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(36),
    action VARCHAR(255),
    amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

TRUNCATE TABLE operational_data;
INSERT INTO operational_data (user_id, action, amount) VALUES ('u1', 'purchase', 100.00);
INSERT INTO operational_data (user_id, action, amount) VALUES ('u1', 'sale', 50.00);
INSERT INTO operational_data (user_id, action, amount) VALUES ('u2', 'purchase', 200.00);
