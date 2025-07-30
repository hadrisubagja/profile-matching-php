<?php
require_once 'database.php';

$database = new Database();
$pdo = $database->connect();

// Create database schema
$sql = "
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'penyelenggara', 'peserta') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criteria table
CREATE TABLE IF NOT EXISTS criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    weight DECIMAL(3,2) NOT NULL,
    target_value INT NOT NULL,
    factor_type ENUM('core', 'secondary') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- GAP weight mapping table
CREATE TABLE IF NOT EXISTS gap_weights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gap_value INT NOT NULL,
    weight_value DECIMAL(3,2) NOT NULL,
    description VARCHAR(255)
);

-- Batches table
CREATE TABLE IF NOT EXISTS batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    max_participants INT DEFAULT 10,
    status ENUM('open', 'closed', 'calculated') DEFAULT 'open',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Participants table
CREATE TABLE IF NOT EXISTS participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    batch_id INT,
    registration_number VARCHAR(50) UNIQUE,
    status ENUM('registered', 'evaluated', 'accepted', 'rejected') DEFAULT 'registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
);

-- Participant scores table
CREATE TABLE IF NOT EXISTS participant_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT,
    criteria_id INT,
    score_value INT NOT NULL,
    gap_value INT,
    weight_value DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id),
    FOREIGN KEY (criteria_id) REFERENCES criteria(id)
);

-- Calculation results table
CREATE TABLE IF NOT EXISTS calculation_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT,
    core_factor_avg DECIMAL(5,3),
    secondary_factor_avg DECIMAL(5,3),
    final_score DECIMAL(5,3),
    ranking INT,
    status ENUM('accepted', 'rejected'),
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id)
);

-- Audit log table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
";

try {
    $pdo->exec($sql);
    echo "Database schema created successfully!\n";
    
    // Insert default admin user
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@spk.com', password_hash('admin123', PASSWORD_DEFAULT), 'System Administrator', 'admin']);
    
    // Insert default criteria based on the PDF
    $criteria = [
        ['Kemampuan Teknis', 'Penilaian kemampuan teknis peserta', 0.25, 4, 'core'],
        ['Pengalaman Kerja', 'Penilaian pengalaman kerja yang relevan', 0.20, 3, 'core'],
        ['Pendidikan', 'Penilaian latar belakang pendidikan', 0.15, 4, 'secondary'],
        ['Kemampuan Komunikasi', 'Penilaian kemampuan komunikasi dan presentasi', 0.15, 3, 'secondary'],
        ['Leadership', 'Penilaian kemampuan kepemimpinan', 0.15, 3, 'secondary'],
        ['Motivasi', 'Penilaian tingkat motivasi dan dedikasi', 0.10, 4, 'secondary']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO criteria (name, description, weight, target_value, factor_type) VALUES (?, ?, ?, ?, ?)");
    foreach ($criteria as $criterion) {
        $stmt->execute($criterion);
    }
    
    // Insert GAP weight mapping based on Profile Matching algorithm
    $gap_weights = [
        [0, 5.0, 'Tidak ada selisih (kompetensi sesuai yang dibutuhkan)'],
        [1, 4.5, 'Kompetensi individu kelebihan 1 tingkat/level'],
        [-1, 4.0, 'Kompetensi individu kekurangan 1 tingkat/level'],
        [2, 3.5, 'Kompetensi individu kelebihan 2 tingkat/level'],
        [-2, 3.0, 'Kompetensi individu kekurangan 2 tingkat/level'],
        [3, 2.5, 'Kompetensi individu kelebihan 3 tingkat/level'],
        [-3, 2.0, 'Kompetensi individu kekurangan 3 tingkat/level'],
        [4, 1.5, 'Kompetensi individu kelebihan 4 tingkat/level'],
        [-4, 1.0, 'Kompetensi individu kekurangan 4 tingkat/level']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO gap_weights (gap_value, weight_value, description) VALUES (?, ?, ?)");
    foreach ($gap_weights as $weight) {
        $stmt->execute($weight);
    }
    
    echo "Default data inserted successfully!\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>