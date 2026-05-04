-- ============================================
-- DROP TABLES BEFORE CREATE
-- ============================================
SET
    FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS accounts;

DROP TABLE IF EXISTS addresses;

DROP TABLE IF EXISTS clients;

DROP TABLE IF EXISTS currencies;

DROP TABLE IF EXISTS employees;

DROP TABLE IF EXISTS roles;

DROP TABLE IF EXISTS transactions;

DROP TABLE IF EXISTS transaction_types;

SET
    FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- TABLE DEFINITIONS
-- ============================================
CREATE TABLE
    IF NOT EXISTS accounts (
        account_id INT NOT NULL AUTO_INCREMENT,
        account_name VARCHAR(22) NOT NULL UNIQUE,
        account_interest_rate DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
        account_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        client_id INT NOT NULL,
        currency_id INT NOT NULL,
        CONSTRAINT accounts_PK PRIMARY KEY (account_id)
    );

CREATE TABLE
    IF NOT EXISTS addresses (
        address_id INTEGER NOT NULL AUTO_INCREMENT,
        address_name VARCHAR(15) NOT NULL UNIQUE,
        CONSTRAINT addresses_PK PRIMARY KEY (address_id)
    );

CREATE TABLE
    IF NOT EXISTS clients (
        client_id INTEGER NOT NULL AUTO_INCREMENT,
        client_name VARCHAR(35) NOT NULL,
        client_egn VARCHAR(10) NOT NULL UNIQUE,
        client_phone VARCHAR(13) UNIQUE,
        address_id INTEGER NOT NULL,
        CONSTRAINT clients_PK PRIMARY KEY (client_id)
    );

CREATE TABLE
    IF NOT EXISTS currencies (
        currency_id INTEGER NOT NULL AUTO_INCREMENT,
        currency_name VARCHAR(25) NOT NULL UNIQUE,
        currency_code VARCHAR(4) NOT NULL UNIQUE,
        CONSTRAINT currencies_PK PRIMARY KEY (currency_id)
    );

CREATE TABLE
    IF NOT EXISTS employees (
        employee_id INTEGER NOT NULL AUTO_INCREMENT,
        employee_name VARCHAR(35) NOT NULL,
        employee_phone VARCHAR(13) UNIQUE,
        role_id INTEGER NOT NULL,
        CONSTRAINT employees_PK PRIMARY KEY (employee_id)
    );

CREATE TABLE
    IF NOT EXISTS roles (
        role_id INTEGER NOT NULL AUTO_INCREMENT,
        role_name VARCHAR(20) NOT NULL UNIQUE,
        CONSTRAINT roles_PK PRIMARY KEY (role_id)
    );

CREATE TABLE
    IF NOT EXISTS transaction_types (
        transaction_type_id INTEGER NOT NULL AUTO_INCREMENT,
        transaction_type_name VARCHAR(15) NOT NULL UNIQUE,
        CONSTRAINT transaction_types_PK PRIMARY KEY (transaction_type_id)
    );

CREATE TABLE
    IF NOT EXISTS transactions (
        transaction_id INTEGER NOT NULL AUTO_INCREMENT,
        transaction_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        transaction_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        transaction_type_id INTEGER NOT NULL,
        client_id INTEGER NOT NULL,
        account_id INTEGER NOT NULL,
        employee_id INTEGER NOT NULL,
        CONSTRAINT transactions_PK PRIMARY KEY (transaction_id)
    );

ALTER TABLE accounts ADD CONSTRAINT accounts_clients_FK FOREIGN KEY (client_id) REFERENCES clients (client_id);

ALTER TABLE accounts ADD CONSTRAINT accounts_currencies_FK FOREIGN KEY (currency_id) REFERENCES currencies (currency_id);

ALTER TABLE clients ADD CONSTRAINT clients_addresses_FK FOREIGN KEY (address_id) REFERENCES addresses (address_id);

ALTER TABLE employees ADD CONSTRAINT employees_roles_FK FOREIGN KEY (role_id) REFERENCES roles (role_id);

ALTER TABLE transactions ADD CONSTRAINT transactions_accounts_FK FOREIGN KEY (account_id) REFERENCES accounts (account_id);

ALTER TABLE transactions ADD CONSTRAINT transactions_clients_FK FOREIGN KEY (client_id) REFERENCES clients (client_id);

ALTER TABLE transactions ADD CONSTRAINT transactions_employees_FK FOREIGN KEY (employee_id) REFERENCES employees (employee_id);

ALTER TABLE transactions ADD CONSTRAINT transactions_transaction_types_FK FOREIGN KEY (transaction_type_id) REFERENCES transaction_types (transaction_type_id);

-- ============================================
-- BIG SET OF DUMMY DATA
-- ============================================
INSERT INTO
    addresses (address_name)
VALUES
    ('Sofia'),
    ('Varna'),
    ('Plovdiv');

INSERT INTO
    currencies (currency_name, currency_code)
VALUES
    ('Euro', 'EUR'),
    ('United States Dollar', 'USD');

INSERT INTO
    roles (role_name)
VALUES
    ('Bank Teller'),
    ('Financial Advisor');

INSERT INTO
    transaction_types (transaction_type_name)
VALUES
    ('Transfer'),
    ('Deposit'),
    ('Withdraw');

-- ============================================
-- CLIENTS (12 records)
-- ============================================
INSERT INTO
    clients (client_name, client_egn, client_phone, address_id)
VALUES
    ('Bogdan Petrov', '8957151105', '+359335220677', 1),
    (
        'Sava Povarenkin',
        '7311050611',
        '+359755399064',
        2
    ),
    ('Kiril Iliev', '9833031267', '+359451764328', 3),
    (
        'Maria Georgieva',
        '9012045523',
        '+359887123456',
        1
    ),
    ('Ivan Stoyanov', '8503127734', '+359877654321', 2),
    (
        'Elena Dimitrova',
        '9207314412',
        '+359866111222',
        3
    ),
    (
        'Georgi Nikolov',
        '7805221198',
        '+359855333444',
        1
    ),
    (
        'Petya Hristova',
        '9404178867',
        '+359844555666',
        2
    ),
    (
        'Dimitar Angelov',
        '8611092245',
        '+359833777888',
        3
    ),
    ('Tanya Koleva', '9109253371', '+359822999000', 1),
    (
        'Stefan Vladimirov',
        '8302196684',
        '+359811222333',
        2
    ),
    (
        'Nadya Todorova',
        '9506084490',
        '+359800444555',
        3
    );

-- ============================================
-- ACCOUNTS (12 records — one per client)
-- ============================================
INSERT INTO
    accounts (
        account_name,
        account_interest_rate,
        account_balance,
        client_id,
        currency_id
    )
VALUES
    ('BG80BNBG96611020345671', 3.3300, 25678.99, 1, 1),
    ('BG80BNBG96611020345672', 3.3300, 199000.43, 2, 2),
    ('BG80BNBG96611020345673', 3.3300, 255897.32, 3, 1),
    ('BG80BNBG96611020345674', 2.5000, 48000.00, 4, 2),
    ('BG80BNBG96611020345675', 1.7500, 12500.50, 5, 1),
    ('BG80BNBG96611020345676', 4.1000, 310000.00, 6, 2),
    ('BG80BNBG96611020345677', 0.5000, 3200.75, 7, 1),
    ('BG80BNBG96611020345678', 3.3300, 87450.20, 8, 2),
    ('BG80BNBG96611020345679', 2.0000, 56000.00, 9, 1),
    ('BG80BNBG96611020345680', 1.2500, 9800.99, 10, 2),
    (
        'BG80BNBG96611020345681',
        3.7500,
        145000.00,
        11,
        1
    ),
    ('BG80BNBG96611020345682', 2.2500, 33750.60, 12, 2);

-- ============================================
-- EMPLOYEES (6 records)
-- ============================================
INSERT INTO
    employees (employee_name, employee_phone, role_id)
VALUES
    ('Dinko Daskalov', '+359567906733', 1),
    ('Asen Kovachev', '+359667442311', 2),
    ('Rositsa Angelova', '+359577123456', 1),
    ('Plamen Georgiev', '+359677654321', 2),
    ('Bilyana Tsvetkov', '+359587111222', 1),
    ('Hristo Manchev', '+359687333444', 2);

-- ============================================
-- TRANSACTIONS (60 records)
-- ============================================
INSERT INTO
    transactions (
        transaction_amount,
        transaction_datetime,
        transaction_type_id,
        client_id,
        account_id,
        employee_id
    )
VALUES
    -- Employee 1 (Dinko)
    (133.32, '2026-01-05 09:15:00', 1, 1, 2, 1),
    (55.12, '2026-01-06 10:30:00', 1, 2, 1, 1),
    (8500.00, '2026-01-07 11:00:00', 2, 3, 3, 1),
    (320.75, '2026-01-08 14:20:00', 3, 4, 4, 1),
    (12000.00, '2026-01-09 09:45:00', 1, 5, 5, 1),
    (450.00, '2026-01-10 13:10:00', 2, 6, 6, 1),
    (99.99, '2026-01-11 15:30:00', 3, 7, 7, 1),
    (7800.50, '2026-01-12 10:00:00', 1, 8, 8, 1),
    (25000.00, '2026-01-13 11:30:00', 2, 9, 9, 1),
    (630.40, '2026-01-14 14:00:00', 3, 10, 10, 1),
    -- Employee 2 (Asen)
    (1200.00, '2026-01-05 09:00:00', 2, 1, 1, 2),
    (88000.00, '2026-01-06 10:15:00', 1, 2, 2, 2),
    (340.00, '2026-01-07 11:45:00', 3, 3, 3, 2),
    (5600.00, '2026-01-08 13:30:00', 1, 4, 4, 2),
    (720.80, '2026-01-09 14:45:00', 2, 5, 5, 2),
    (45000.00, '2026-01-10 09:30:00', 1, 6, 6, 2),
    (180.25, '2026-01-11 10:45:00', 3, 7, 7, 2),
    (9300.00, '2026-01-12 12:00:00', 2, 8, 8, 2),
    (2750.00, '2026-01-13 13:15:00', 3, 9, 9, 2),
    (67000.00, '2026-01-14 14:30:00', 1, 10, 10, 2),
    -- Employee 3 (Rositsa)
    (500.00, '2026-02-01 09:00:00', 2, 1, 1, 3),
    (13500.00, '2026-02-02 10:30:00', 1, 2, 2, 3),
    (275.60, '2026-02-03 11:00:00', 3, 3, 3, 3),
    (8900.00, '2026-02-04 13:45:00', 1, 4, 4, 3),
    (420.00, '2026-02-05 14:00:00', 2, 5, 5, 3),
    (31000.00, '2026-02-06 09:15:00', 1, 6, 6, 3),
    (650.75, '2026-02-07 10:30:00', 3, 7, 7, 3),
    (4400.00, '2026-02-08 11:45:00', 2, 8, 8, 3),
    (19500.00, '2026-02-09 13:00:00', 1, 9, 9, 3),
    (890.30, '2026-02-10 14:15:00', 3, 10, 10, 3),
    -- Employee 4 (Plamen)
    (760.00, '2026-02-11 09:30:00', 2, 1, 1, 4),
    (22000.00, '2026-02-12 10:45:00', 1, 2, 2, 4),
    (115.40, '2026-02-13 11:15:00', 3, 3, 3, 4),
    (6700.00, '2026-02-14 13:30:00', 1, 4, 4, 4),
    (330.90, '2026-02-15 14:45:00', 2, 5, 5, 4),
    (54000.00, '2026-02-16 09:00:00', 1, 6, 6, 4),
    (210.00, '2026-02-17 10:15:00', 3, 7, 7, 4),
    (7100.00, '2026-02-18 11:30:00', 2, 8, 8, 4),
    (3300.00, '2026-02-19 12:45:00', 3, 9, 9, 4),
    (41500.00, '2026-02-20 14:00:00', 1, 10, 10, 4),
    -- Employee 5 (Bilyana)
    (950.00, '2026-03-01 09:15:00', 2, 11, 11, 5),
    (18000.00, '2026-03-02 10:30:00', 1, 12, 12, 5),
    (430.25, '2026-03-03 11:45:00', 3, 1, 1, 5),
    (7500.00, '2026-03-04 13:00:00', 1, 2, 2, 5),
    (560.00, '2026-03-05 14:15:00', 2, 3, 3, 5),
    (29000.00, '2026-03-06 09:30:00', 1, 4, 4, 5),
    (380.50, '2026-03-07 10:45:00', 3, 5, 5, 5),
    (6200.00, '2026-03-08 12:00:00', 2, 6, 6, 5),
    (14700.00, '2026-03-09 13:15:00', 1, 7, 7, 5),
    (710.80, '2026-03-10 14:30:00', 3, 8, 8, 5),
    -- Employee 6 (Hristo)
    (1100.00, '2026-03-11 09:00:00', 2, 9, 9, 6),
    (36000.00, '2026-03-12 10:15:00', 1, 10, 10, 6),
    (290.70, '2026-03-13 11:30:00', 3, 11, 11, 6),
    (8100.00, '2026-03-14 13:45:00', 1, 12, 12, 6),
    (480.00, '2026-03-15 14:00:00', 2, 1, 1, 6),
    (62000.00, '2026-03-16 09:15:00', 1, 2, 2, 6),
    (175.30, '2026-03-17 10:30:00', 3, 3, 3, 6),
    (5400.00, '2026-03-18 11:45:00', 2, 4, 4, 6),
    (23500.00, '2026-03-19 13:00:00', 1, 5, 5, 6),
    (820.60, '2026-03-20 14:15:00', 3, 6, 6, 6);