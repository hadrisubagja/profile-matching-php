<?php
require_once 'classes/Auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    if (isset($_POST['login'])) {
        $result = $auth->login($_POST['username'], $_POST['password']);
        if ($result['success']) {
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['register'])) {
        $result = $auth->register([
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'full_name' => $_POST['full_name'],
            'role' => 'peserta' // Default role for registration
        ]);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login - SPK Profile Matching';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row min-vh-100">
        <!-- Left side - Branding -->
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary">
            <div class="text-center text-white">
                <div class="mb-4">
                    <i class="bi bi-graph-up-arrow" style="font-size: 5rem;"></i>
                </div>
                <h1 class="display-4 fw-bold mb-3">SPK Profile Matching</h1>
                <p class="lead mb-4">Sistem Pendukung Keputusan menggunakan metode Profile Matching untuk seleksi peserta berdasarkan kriteria yang telah ditentukan.</p>
                <div class="row text-center">
                    <div class="col-4">
                        <i class="bi bi-people-fill mb-2" style="font-size: 2rem;"></i>
                        <p class="small">Multi-Role System</p>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-calculator-fill mb-2" style="font-size: 2rem;"></i>
                        <p class="small">Smart Calculation</p>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-bar-chart-fill mb-2" style="font-size: 2rem;"></i>
                        <p class="small">Detailed Reports</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right side - Login/Register Forms -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="text-center mb-4">
                    <div class="d-lg-none mb-3">
                        <i class="bi bi-graph-up-arrow text-primary" style="font-size: 3rem;"></i>
                        <h2 class="text-primary">SPK Profile Matching</h2>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <div class="card shadow" id="loginForm">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">Masuk ke Sistem</h3>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username atau Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <div class="invalid-feedback">Username atau email harus diisi.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback">Password harus diisi.</div>
                                </div>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                            </button>
                        </form>
                        <div class="text-center">
                            <p class="mb-0">Belum punya akun? <a href="#" onclick="showRegisterForm()" class="text-decoration-none">Daftar di sini</a></p>
                        </div>
                    </div>
                </div>
                
                <!-- Register Form (Hidden by default) -->
                <div class="card shadow d-none" id="registerForm">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">Daftar Akun Baru</h3>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="reg_full_name" class="form-label">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control" id="reg_full_name" name="full_name" required>
                                    <div class="invalid-feedback">Nama lengkap harus diisi.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="reg_username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="reg_username" name="username" pattern="[a-zA-Z0-9_]{3,20}" required>
                                    <div class="invalid-feedback">Username 3-20 karakter, hanya huruf, angka, dan underscore.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="reg_email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="reg_email" name="email" required>
                                    <div class="invalid-feedback">Email harus valid.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="reg_password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="reg_password" name="password" minlength="6" required>
                                    <div class="invalid-feedback">Password minimal 6 karakter.</div>
                                </div>
                            </div>
                            <button type="submit" name="register" class="btn btn-success w-100 mb-3">
                                <i class="bi bi-person-plus me-2"></i>Daftar
                            </button>
                        </form>
                        <div class="text-center">
                            <p class="mb-0">Sudah punya akun? <a href="#" onclick="showLoginForm()" class="text-decoration-none">Masuk di sini</a></p>
                        </div>
                    </div>
                </div>
                
                <!-- Demo Accounts Info -->
                <div class="card mt-4 bg-light">
                    <div class="card-body p-3">
                        <h6 class="card-title"><i class="bi bi-info-circle me-2"></i>Akun Demo</h6>
                        <small class="text-muted">
                            <strong>Admin:</strong> admin / admin123<br>
                            Atau daftar sebagai peserta untuk mencoba sistem.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showRegisterForm() {
    document.getElementById('loginForm').classList.add('d-none');
    document.getElementById('registerForm').classList.remove('d-none');
}

function showLoginForm() {
    document.getElementById('registerForm').classList.add('d-none');
    document.getElementById('loginForm').classList.remove('d-none');
}
</script>

<?php include 'includes/footer.php'; ?>