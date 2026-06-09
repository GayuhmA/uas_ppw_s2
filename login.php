<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = mysqli_prepare($conn, "SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                redirect('pages/dashboard.php');
            } else {
                $error = 'Email atau kata sandi tidak sesuai.';
            }
        } else {
            $error = 'Email atau kata sandi tidak sesuai.';
        }
    } else {
        $error = 'Silakan isi email dan kata sandi.';
    }
}

$pageTitle = 'Login Portal';
$hideNavbar = true; 
$hideFooter = true; 
require_once __DIR__ . '/includes/header.php';
?>

<style>
    body {
        background-color: #F6F9FC;
        color: #1F2A37;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .login-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2rem 1rem;
    }

    .login-brand {
        text-align: center;
        margin-bottom: 2rem;
        text-decoration: none;
    }

    .login-brand .brand-mark {
        display: inline-flex;
        background-color: #2A94DB;
        color: #fff;
        width: 36px; height: 36px;
        align-items: center; justify-content: center;
        border-radius: 6px;
        font-weight: 700;
        font-size: 1.125rem;
        margin-right: 0.5rem;
    }

    .login-brand .brand-text {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1F2A37;
        letter-spacing: -0.01em;
    }

    .login-card {
        background: #FFFFFF;
        border: 1px solid #D9E4EE;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        width: 100%;
        max-width: 400px;
        padding: 2.5rem;
    }

    .login-card h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1F2A37;
        margin-bottom: 0.5rem;
        text-align: center;
        letter-spacing: -0.01em;
    }

    .login-card p.subtitle {
        color: #667085;
        font-size: 0.875rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .form-label {
        font-weight: 500;
        color: #1F2A37;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border: 1px solid #D9E4EE;
        border-radius: 6px;
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
        color: #1F2A37;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: #2A94DB;
        box-shadow: 0 0 0 3px rgba(42, 148, 219, 0.15);
        outline: none;
    }

    .form-control::placeholder {
        color: #9CA3AF;
    }

    .btn-primary-custom {
        background-color: #2A94DB;
        color: #FFFFFF;
        border: none;
        border-radius: 6px;
        padding: 0.625rem 1rem;
        font-weight: 600;
        font-size: 0.875rem;
        width: 100%;
        transition: background-color 0.2s;
        margin-top: 1rem;
    }

    .btn-primary-custom:hover {
        background-color: #1F6FA8;
    }

    .custom-alert {
        background-color: #FEE2E2;
        border: 1px solid #FCA5A5;
        color: #991B1B;
        padding: 0.75rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
    }
</style>

<div class="login-wrapper">
    <a href="<?= url('index.php') ?>" class="login-brand d-flex align-items-center justify-content-center">
        <span class="brand-mark">R</span>
        <span class="brand-text">Reservasi Ruang</span>
    </a>

    <div class="login-card">
        <h1>Akses Dashboard</h1>
        <p class="subtitle">Masuk dengan kredensial sistem Anda</p>

        <?php if ($error): ?>
            <div class="custom-alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('login.php') ?>" data-validate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                    placeholder="nama@kampus.ac.id" 
                    value="<?= e($_POST['email'] ?? '') ?>"
                    data-required="true" data-message="Email wajib diisi">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" class="form-control" id="password" name="password" 
                    placeholder="••••••••"
                    data-required="true" data-message="Kata sandi wajib diisi">
            </div>
            
            <button type="submit" class="btn-primary-custom">Masuk ke Sistem</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
