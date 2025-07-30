<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = $_SESSION['role'] ?? '';
?>

<div class="sidebar collapse d-lg-block" id="sidebar">
    <div class="p-3">
        <div class="text-center mb-4">
            <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
            </div>
            <h6 class="text-white mt-2 mb-0"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h6>
            <small class="text-white-50"><?php echo ucfirst($userRole); ?></small>
        </div>
        
        <nav class="nav flex-column">
            <!-- Dashboard - Available for all roles -->
            <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="/dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
            
            <?php if ($userRole === 'admin'): ?>
                <!-- Admin Menu -->
                <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="/admin/users.php">
                    <i class="bi bi-people me-2"></i>Kelola Pengguna
                </a>
                <a class="nav-link <?php echo $currentPage === 'criteria.php' ? 'active' : ''; ?>" href="/admin/criteria.php">
                    <i class="bi bi-list-check me-2"></i>Kelola Kriteria
                </a>
                <a class="nav-link <?php echo $currentPage === 'gap-weights.php' ? 'active' : ''; ?>" href="/admin/gap-weights.php">
                    <i class="bi bi-calculator me-2"></i>Bobot GAP
                </a>
                <a class="nav-link <?php echo $currentPage === 'audit-logs.php' ? 'active' : ''; ?>" href="/admin/audit-logs.php">
                    <i class="bi bi-journal-text me-2"></i>Log Audit
                </a>
            <?php endif; ?>
            
            <?php if ($userRole === 'admin' || $userRole === 'penyelenggara'): ?>
                <!-- Admin & Penyelenggara Menu -->
                <a class="nav-link <?php echo $currentPage === 'batches.php' ? 'active' : ''; ?>" href="/batches.php">
                    <i class="bi bi-collection me-2"></i>Kelola Batch
                </a>
                <a class="nav-link <?php echo $currentPage === 'participants.php' ? 'active' : ''; ?>" href="/participants.php">
                    <i class="bi bi-person-lines-fill me-2"></i>Kelola Peserta
                </a>
                <a class="nav-link <?php echo $currentPage === 'calculations.php' ? 'active' : ''; ?>" href="/calculations.php">
                    <i class="bi bi-calculator-fill me-2"></i>Perhitungan
                </a>
                <a class="nav-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>" href="/reports.php">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan
                </a>
            <?php endif; ?>
            
            <?php if ($userRole === 'peserta'): ?>
                <!-- Peserta Menu -->
                <a class="nav-link <?php echo $currentPage === 'registration.php' ? 'active' : ''; ?>" href="/registration.php">
                    <i class="bi bi-person-plus me-2"></i>Pendaftaran
                </a>
                <a class="nav-link <?php echo $currentPage === 'my-scores.php' ? 'active' : ''; ?>" href="/my-scores.php">
                    <i class="bi bi-bar-chart me-2"></i>Nilai Saya
                </a>
                <a class="nav-link <?php echo $currentPage === 'my-results.php' ? 'active' : ''; ?>" href="/my-results.php">
                    <i class="bi bi-trophy me-2"></i>Hasil Seleksi
                </a>
            <?php endif; ?>
        </nav>
    </div>
</div>