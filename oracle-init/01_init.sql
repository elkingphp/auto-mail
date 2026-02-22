-- Ensure we are in the pluggable database and using the app user schema
-- The gvenzl image might already be in the PDB, but this makes it explicit.
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = POST_ADMIN;

-- Create tables
CREATE TABLE OFFICES (
    OFFICE_ID NUMBER PRIMARY KEY,
    OFFICE_NAME VARCHAR2(100),
    CITY VARCHAR2(100),
    REGION VARCHAR2(100)
);

CREATE TABLE SHIPMENTS (
    SHIPMENT_ID NUMBER PRIMARY KEY,
    TRACKING_NUMBER VARCHAR2(20) UNIQUE,
    OFFICE_ID NUMBER,
    SENDER_NAME VARCHAR2(100),
    RECEIVER_NAME VARCHAR2(100),
    WEIGHT NUMBER(10,2),
    STATUS VARCHAR2(20),
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT FK_OFFICE FOREIGN KEY (OFFICE_ID) REFERENCES OFFICES(OFFICE_ID)
);

-- Generate 3,000 Offices
DECLARE
BEGIN
    FOR i IN 1..3000 LOOP
        INSERT INTO OFFICES (OFFICE_ID, OFFICE_NAME, CITY, REGION)
        VALUES (i, 'Office ' || i, 'City ' || MOD(i, 50), 'Region ' || MOD(i, 10));
    END LOOP;
    COMMIT;
END;
/

-- Generate 500,000 Shipments
DECLARE
    batch_size NUMBER := 10000;
BEGIN
    FOR i IN 1..500000 LOOP
        INSERT INTO SHIPMENTS (SHIPMENT_ID, TRACKING_NUMBER, OFFICE_ID, SENDER_NAME, RECEIVER_NAME, WEIGHT, STATUS)
        VALUES (
            i, 
            'EG' || LPAD(i, 10, '0') || 'POST', 
            TRUNC(DBMS_RANDOM.VALUE(1, 3001)),
            'Sender ' || i,
            'Receiver ' || i,
            DBMS_RANDOM.VALUE(0.1, 50.0),
            CASE MOD(i, 4) 
                WHEN 0 THEN 'Pending' 
                WHEN 1 THEN 'In Transit' 
                WHEN 2 THEN 'Delivered' 
                ELSE 'Returned' 
            END
        );
        
        IF MOD(i, batch_size) = 0 THEN
            COMMIT;
        END IF;
    END LOOP;
    COMMIT;
END;
/
