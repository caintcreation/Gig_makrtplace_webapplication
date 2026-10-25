-- Database schema for CAINT EARN HUB
-- Supports verified paying platforms, gig applications, Paystack/M-Pesa direct STK prompt, live chat, and multi-role dashboards

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('worker', 'client', 'admin') NOT NULL DEFAULT 'worker',
    email VARCHAR(191) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    status ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'active',
    reset_token VARCHAR(100) NULL,
    reset_token_expiry DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role),
    INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    display_name VARCHAR(150) NOT NULL,
    headline VARCHAR(255) NULL,
    bio TEXT NULL,
    skills TEXT NULL,
    location VARCHAR(100) NULL DEFAULT 'Nairobi, Kenya',
    portfolio_url VARCHAR(255) NULL,
    avatar_url VARCHAR(255) NULL,
    availability VARCHAR(100) NULL DEFAULT 'Available now',
    hourly_rate DECIMAL(10,2) NULL DEFAULT 0.00,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    payout_method VARCHAR(50) NULL DEFAULT 'M-Pesa',
    payout_details VARCHAR(255) NULL,
    payment_proof VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    icon_name VARCHAR(50) NULL DEFAULT 'tag',
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platforms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(191) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    short_desc TEXT NOT NULL,
    full_desc LONGTEXT NOT NULL,
    requirements TEXT NULL,
    steps_guide TEXT NULL,
    earning_potential VARCHAR(150) NOT NULL DEFAULT 'KSh 1,500 - KSh 4,500 / task',
    payout_method VARCHAR(150) NOT NULL DEFAULT 'M-Pesa, PayPal, Bank',
    payout_proofs JSON NULL,
    logo_image VARCHAR(255) NULL,
    task_image VARCHAR(255) NULL,
    work_type ENUM('Remote', 'Hybrid', 'On-site') NOT NULL DEFAULT 'Remote',
    token_price DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    access_url VARCHAR(255) NOT NULL,
    verification_status ENUM('verified', 'pending', 'rejected') NOT NULL DEFAULT 'verified',
    listing_status ENUM('open', 'closed', 'waitlist') NOT NULL DEFAULT 'open',
    views_count INT NOT NULL DEFAULT 0,
    unlocks_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    INDEX idx_verification (verification_status),
    INDEX idx_listing_status (listing_status),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS unlock_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_id INT NOT NULL,
    user_id INT NULL,
    phone VARCHAR(30) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    checkout_id VARCHAR(100) NOT NULL UNIQUE,
    mpesa_receipt VARCHAR(50) NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    access_token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_tx_phone (phone),
    INDEX idx_tx_status (status),
    INDEX idx_tx_platform (platform_id),
    FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_id INT NOT NULL,
    user_id INT NULL,
    applicant_name VARCHAR(150) NOT NULL,
    applicant_email VARCHAR(191) NOT NULL,
    applicant_phone VARCHAR(30) NOT NULL,
    cover_note TEXT NOT NULL,
    portfolio_links VARCHAR(255) NULL,
    resume_attachment VARCHAR(255) NULL,
    status ENUM('submitted', 'reviewed', 'shortlisted', 'rejected') NOT NULL DEFAULT 'submitted',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_app_platform (platform_id),
    INDEX idx_app_email (applicant_email),
    FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_token VARCHAR(64) NOT NULL UNIQUE,
    visitor_name VARCHAR(150) NOT NULL DEFAULT 'Guest Visitor',
    visitor_phone VARCHAR(30) NULL,
    user_id INT NULL,
    status ENUM('active', 'closed') NOT NULL DEFAULT 'active',
    last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conv_token (visitor_token),
    INDEX idx_conv_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_type ENUM('visitor', 'admin', 'user') NOT NULL DEFAULT 'visitor',
    sender_name VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_msg_conv (conversation_id),
    INDEX idx_msg_read (is_read),
    FOREIGN KEY (conversation_id) REFERENCES support_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_platforms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_platform (user_id, platform_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS device_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_token TEXT NOT NULL,
    device_type VARCHAR(50) NOT NULL DEFAULT 'web',
    phone VARCHAR(30) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
