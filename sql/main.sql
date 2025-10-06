-- User and Role Management

-- Table to store user roles
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Table to store user information
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- To be stored as a hash
    role_id INT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Table for additional staff details
CREATE TABLE staff_details (
    staff_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    qr_code_path VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Inventory Management

-- Table to store different cylinder types
CREATE TABLE cylinder_types (
    cylinder_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE
);

-- Table to manage the inventory of cylinders
CREATE TABLE inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    cylinder_type_id INT NOT NULL,
    opening_stock_full INT DEFAULT 0,
    opening_stock_empty INT DEFAULT 0,
    closing_stock_full INT DEFAULT 0,
    closing_stock_empty INT DEFAULT 0,
    inventory_date DATE NOT NULL,
    UNIQUE(cylinder_type_id, inventory_date),
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(cylinder_type_id)
);

-- Vehicle Management

-- Table to store vehicle information
CREATE TABLE vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(50) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT 1
);

-- Daily Operations and Deliverables

-- Table to record daily cylinder transactions by delivery persons
CREATE TABLE daily_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_person_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    approved_by_keeper_id INT,
    FOREIGN KEY (delivery_person_id) REFERENCES users(user_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
    FOREIGN KEY (approved_by_keeper_id) REFERENCES users(user_id)
);

-- Table for the details of each transaction
CREATE TABLE transaction_details (
    transaction_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    cylinder_type_id INT NOT NULL,
    pickup_full INT DEFAULT 0,
    pickup_empty INT DEFAULT 0,
    return_full INT DEFAULT 0,
    return_empty INT DEFAULT 0,
    FOREIGN KEY (transaction_id) REFERENCES daily_transactions(transaction_id),
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(cylinder_type_id)
);

-- Accounting and Invoice Management

-- Table to store main invoice data
CREATE TABLE invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL,
    vehicle_id INT,
    total_cylinders INT,
    total_amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
);

-- Linking table for invoices and delivery persons with cylinder details
CREATE TABLE invoice_delivery_details (
    invoice_delivery_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    delivery_person_id INT NOT NULL,
    cylinder_type_id INT NOT NULL,
    quantity_full INT,
    quantity_empty INT,
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id),
    FOREIGN KEY (delivery_person_id) REFERENCES users(user_id),
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(cylinder_type_id)
);

-- Table to record collections from delivery persons
CREATE TABLE collections (
    collection_id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_person_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    collection_date DATE NOT NULL,
    is_approved BOOLEAN DEFAULT 0,
    approved_by INT,
    approval_timestamp TIMESTAMP NULL,
    FOREIGN KEY (delivery_person_id) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- Table to record daily expenses
CREATE TABLE expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Attendance Management
-- Table to manage staff attendance
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time TIME,
    scanned_by_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, attendance_date),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (scanned_by_id) REFERENCES users(user_id)
);