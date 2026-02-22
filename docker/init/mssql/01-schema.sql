-- Unified Testing Schema for MSSQL
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'operational_data')
BEGIN
    CREATE TABLE operational_data (
        id INT IDENTITY(1,1) PRIMARY KEY,
        user_id VARCHAR(36),
        action VARCHAR(255),
        amount DECIMAL(10,2),
        created_at DATETIME DEFAULT GETDATE()
    );
END

TRUNCATE TABLE operational_data;
INSERT INTO operational_data (user_id, action, amount) VALUES ('u1', 'purchase', 100.00);
INSERT INTO operational_data (user_id, action, amount) VALUES ('u1', 'sale', 50.00);
INSERT INTO operational_data (user_id, action, amount) VALUES ('u2', 'purchase', 200.00);
