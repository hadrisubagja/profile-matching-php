<?php
require_once 'classes/Auth.php';
require_once 'config/database.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$database = new Database();
$pdo = $database->connect();

// Get statistics based on user role
$stats = [];

if ($auth->hasRole('admin')) {
    // Admin stats
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = 1");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_batches FROM batches");
    $stats['total_batches'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_participants FROM participants");
    $stats['total_participants'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_criteria FROM criteria WHERE is_active = 1");
    $stats['total_criteria'] = $stmt->fetchColumn();
    
} elseif ($auth->hasRole('penyelenggara')) {
    // Penyelenggara stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as my_batches FROM batches WHERE created_by = ?");
    $stmt->execute([$currentUser['id']]);
    $stats['my_batches'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_participants FROM participants");
    $stats['total_participants'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as calculated_batches FROM batches WHERE status = 'calculated'");
    $stats['calculated_batches'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as accepted_participants FROM calculation_results WHERE status = 'accepted'");
    $stats['accepted_participants'] = $stmt->fetchColumn();
    
} else {
    // Peserta stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as my_registrations FROM participants WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
    $stats['my_registrations'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as my_results 
        FROM calculation_results cr 
        JOIN participants p ON cr.participant_id = p.id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$currentUser['id']]);
    $stats['my_results'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as accepted_results 
        FROM calculation_results cr 
        JOIN participants p ON cr.participant_id = p.id 
        WHERE p.user_id = ? AND cr.status = 'accepted'
    ");
    $stmt->execute([$currentUser['id']]);
    $stats['accepted_results'] = $stmt->fetchColumn();
}

// Recent activities
$recentActivities = [];
if ($auth->hasAnyRole(['admin', 'penyelenggara'])) {
    $stmt = $pdo->query("
        SELECT 
            al.action, 
            al.table_name, 
            al.created_at,
            u.full_name 
        FROM audit_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $recentActivities = $stmt->fetchAll();
}

$pageTitle = 'Dashboard - SPK Profile Matching';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 p-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <div class="content-wrapper">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <p class="text-muted">Selamat datang, <?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                    </div>
                    <div class="text-muted">
                        <i class="bi bi-calendar3 me-2"></i><?php echo date('d F Y'); ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <?php if ($auth->hasRole('admin')): ?>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Pengguna</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['total_users']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Batch</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['total_batches']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-collection" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Peserta</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['total_participants']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-person-lines-fill" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Kriteria</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['total_criteria']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-list-check" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($auth->hasRole('penyelenggara')): ?>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Batch Saya</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['my_batches']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-collection" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Peserta</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['total_participants']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-person-lines-fill" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Batch Terhitung</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['calculated_batches']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-calculator-fill" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Peserta Diterima</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['accepted_participants']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-trophy" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Pendaftaran Saya</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['my_registrations']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-person-plus" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Hasil Seleksi</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['my_results']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-bar-chart" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Diterima</h6>
                                            <div class="display-6 fw-bold"><?php echo $stats['accepted_results']; ?></div>
                                        </div>
                                        <div class="text-white-50">
                                            <i class="bi bi-trophy" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Activities (Admin & Penyelenggara only) -->
                <?php if ($auth->hasAnyRole(['admin', 'penyelenggara']) && !empty($recentActivities)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentActivities as $activity): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                            melakukan <code><?php echo htmlspecialchars($activity['action']); ?></code>
                                            pada <em><?php echo htmlspecialchars($activity['table_name']); ?></em>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-lightning me-2"></i>Aksi Cepat
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($auth->hasRole('admin')): ?>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/admin/users.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-people mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Kelola Pengguna</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/admin/criteria.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-list-check mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Kelola Kriteria</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/batches.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-collection mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Kelola Batch</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/reports.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-file-earmark-bar-graph mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Lihat Laporan</span>
                                            </a>
                                        </div>
                                    <?php elseif ($auth->hasRole('penyelenggara')): ?>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/batches.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-collection mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Kelola Batch</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/participants.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-person-lines-fill mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Kelola Peserta</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/calculations.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-calculator-fill mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Perhitungan</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <a href="/reports.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-file-earmark-bar-graph mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Laporan</span>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <a href="/registration.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-person-plus mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Daftar Batch</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <a href="/my-scores.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-bar-chart mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Nilai Saya</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <a href="/my-results.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center align-items-center py-3">
                                                <i class="bi bi-trophy mb-2" style="font-size: 1.5rem;"></i>
                                                <span>Hasil Seleksi</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>