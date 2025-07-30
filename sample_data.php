<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->connect();

try {
    $pdo->beginTransaction();
    
    // Create sample users (Penyelenggara and Peserta)
    $sampleUsers = [
        // Penyelenggara
        ['penyelenggara1', 'penyelenggara1@spk.com', 'Penyelenggara Satu', 'penyelenggara'],
        ['penyelenggara2', 'penyelenggara2@spk.com', 'Penyelenggara Dua', 'penyelenggara'],
        
        // Peserta (35 orang untuk testing 3+ batch)
        ['peserta001', 'peserta001@spk.com', 'Ahmad Fauzan', 'peserta'],
        ['peserta002', 'peserta002@spk.com', 'Siti Nurhaliza', 'peserta'],
        ['peserta003', 'peserta003@spk.com', 'Budi Santoso', 'peserta'],
        ['peserta004', 'peserta004@spk.com', 'Dewi Sartika', 'peserta'],
        ['peserta005', 'peserta005@spk.com', 'Eko Prasetyo', 'peserta'],
        ['peserta006', 'peserta006@spk.com', 'Fitri Handayani', 'peserta'],
        ['peserta007', 'peserta007@spk.com', 'Gunawan Wijaya', 'peserta'],
        ['peserta008', 'peserta008@spk.com', 'Hesti Purwanti', 'peserta'],
        ['peserta009', 'peserta009@spk.com', 'Indra Permana', 'peserta'],
        ['peserta010', 'peserta010@spk.com', 'Joko Susilo', 'peserta'],
        ['peserta011', 'peserta011@spk.com', 'Kartika Sari', 'peserta'],
        ['peserta012', 'peserta012@spk.com', 'Lukman Hakim', 'peserta'],
        ['peserta013', 'peserta013@spk.com', 'Maya Sari', 'peserta'],
        ['peserta014', 'peserta014@spk.com', 'Nanda Pratama', 'peserta'],
        ['peserta015', 'peserta015@spk.com', 'Olivia Putri', 'peserta'],
        ['peserta016', 'peserta016@spk.com', 'Putra Wijaya', 'peserta'],
        ['peserta017', 'peserta017@spk.com', 'Qori Ramadhan', 'peserta'],
        ['peserta018', 'peserta018@spk.com', 'Rina Wati', 'peserta'],
        ['peserta019', 'peserta019@spk.com', 'Sandi Kurnia', 'peserta'],
        ['peserta020', 'peserta020@spk.com', 'Tina Marlina', 'peserta'],
        ['peserta021', 'peserta021@spk.com', 'Umar Bakri', 'peserta'],
        ['peserta022', 'peserta022@spk.com', 'Vera Novita', 'peserta'],
        ['peserta023', 'peserta023@spk.com', 'Wahyu Saputra', 'peserta'],
        ['peserta024', 'peserta024@spk.com', 'Xenia Putri', 'peserta'],
        ['peserta025', 'peserta025@spk.com', 'Yusuf Rahman', 'peserta'],
        ['peserta026', 'peserta026@spk.com', 'Zahra Aini', 'peserta'],
        ['peserta027', 'peserta027@spk.com', 'Arif Budiman', 'peserta'],
        ['peserta028', 'peserta028@spk.com', 'Bella Safitri', 'peserta'],
        ['peserta029', 'peserta029@spk.com', 'Candra Kirana', 'peserta'],
        ['peserta030', 'peserta030@spk.com', 'Dodi Setiawan', 'peserta'],
        ['peserta031', 'peserta031@spk.com', 'Elsa Maharani', 'peserta'],
        ['peserta032', 'peserta032@spk.com', 'Fajar Sidik', 'peserta'],
        ['peserta033', 'peserta033@spk.com', 'Gita Savitri', 'peserta'],
        ['peserta034', 'peserta034@spk.com', 'Hendra Gunawan', 'peserta'],
        ['peserta035', 'peserta035@spk.com', 'Ina Soraya', 'peserta']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, full_name, role, password) VALUES (?, ?, ?, ?, ?)");
    foreach ($sampleUsers as $user) {
        $stmt->execute([
            $user[0], 
            $user[1], 
            $user[2], 
            $user[3], 
            password_hash('123456', PASSWORD_DEFAULT)
        ]);
    }
    
    // Create sample batches
    $batches = [
        ['Batch Seleksi Januari 2024', 'Batch seleksi periode Januari 2024', 10, 'open'],
        ['Batch Seleksi Februari 2024', 'Batch seleksi periode Februari 2024', 10, 'open'],
        ['Batch Seleksi Maret 2024', 'Batch seleksi periode Maret 2024', 10, 'open'],
        ['Batch Seleksi April 2024', 'Batch seleksi periode April 2024', 10, 'closed']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO batches (name, description, max_participants, status, created_by) VALUES (?, ?, ?, ?, 1)");
    foreach ($batches as $batch) {
        $stmt->execute($batch);
    }
    
    // Get user IDs for participants
    $stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'peserta' ORDER BY id");
    $participants = $stmt->fetchAll();
    
    // Get batch IDs
    $stmt = $pdo->query("SELECT id FROM batches ORDER BY id");
    $batchIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Create sample participants (distribute among batches)
    $participantIndex = 0;
    foreach ($batchIds as $batchId) {
        for ($i = 0; $i < 10 && $participantIndex < count($participants); $i++) {
            $participant = $participants[$participantIndex];
            $regNumber = 'REG' . str_pad($batchId, 2, '0', STR_PAD_LEFT) . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO participants (user_id, batch_id, registration_number, status) VALUES (?, ?, ?, 'registered')");
            $stmt->execute([$participant['id'], $batchId, $regNumber]);
            
            $participantId = $pdo->lastInsertId();
            if ($participantId) {
                // Create sample scores for each participant
                $criteriaIds = [1, 2, 3, 4, 5, 6]; // Based on default criteria
                
                foreach ($criteriaIds as $criteriaId) {
                    // Generate random scores between 1-5 with some variation
                    $score = rand(1, 5);
                    
                    // Add some realistic variation (some participants better in certain criteria)
                    if ($participantIndex % 3 === 0) { // Every 3rd participant is strong in technical
                        if ($criteriaId === 1) $score = max($score, 4);
                    }
                    if ($participantIndex % 5 === 0) { // Every 5th participant is strong in leadership
                        if ($criteriaId === 5) $score = max($score, 4);
                    }
                    
                    $stmt = $pdo->prepare("INSERT IGNORE INTO participant_scores (participant_id, criteria_id, score_value) VALUES (?, ?, ?)");
                    $stmt->execute([$participantId, $criteriaId, $score]);
                }
            }
            
            $participantIndex++;
        }
    }
    
    $pdo->commit();
    echo "Sample data created successfully!\n";
    echo "Created:\n";
    echo "- " . count($sampleUsers) . " sample users\n";
    echo "- " . count($batches) . " sample batches\n";
    echo "- " . min($participantIndex, count($participants)) . " sample participants with scores\n\n";
    
    echo "Login credentials:\n";
    echo "Admin: admin / admin123\n";
    echo "Penyelenggara: penyelenggara1 / 123456\n";
    echo "Peserta: peserta001 / 123456 (atau peserta002, peserta003, dst.)\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error creating sample data: " . $e->getMessage() . "\n";
}
?>