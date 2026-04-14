<?php
require_once 'conexion/session.php';
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetCare | Iniciar Sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(120deg, #e0f2e5, #d4e8f0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .login-header {
            background: linear-gradient(135deg, #1f3e4b, #2c5a6e);
            padding: 35px 30px;
            text-align: center;
            color: white;
        }

        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
            color: #6bcb77;
        }
        .alert {
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideIn 0.4s ease-out;
        }

        .alert i {
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .alert-danger ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .alert-danger li {
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-danger li i {
            color: #ef4444;
        }

        .alert-success {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-left: 4px solid #22c55e;
            color: #166534;
        }

        .alert-success i {
            color: #22c55e;
        }

        .login-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .login-header p {
            opacity: 0.9;
            margin-top: 8px;
        }

        .login-form {
            padding: 40px 35px;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #9bb7c4;
            font-size: 1.1rem;
        }

        .input-group input {
            width: 100%;
            padding: 16px 18px 16px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 60px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .input-group input:focus {
            outline: none;
            border-color: #6bcb77;
            box-shadow: 0 0 0 3px rgba(107, 203, 119, 0.2);
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            font-size: 0.85rem;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a6272;
        }

        .forgot-link {
            color: #6bcb77;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-login {
            width: 100%;
            background: #6bcb77;
            border: none;
            padding: 16px;
            border-radius: 60px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .btn-login:hover {
            background: #54b561;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(107, 203, 119, 0.3);
        }

        .register-link {
            text-align: center;
            font-size: 0.9rem;
            color: #5f7f8f;
        }

        .register-link a {
            color: #6bcb77;
            text-decoration: none;
            font-weight: 600;
        }

        .demo-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 20px;
            margin-top: 20px;
            text-align: center;
            font-size: 0.8rem;
            color: #4a6272;
            border: 1px solid #eef2f0;
        }

        @media (max-width: 550px) {
            .login-form {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-paw"></i>
            <h2>Bienvenido de vuelta</h2>
            <p>Accede a tu cuenta de VetCare</p>
        </div>
        <div class="login-form">
            <form id="loginForm" method="POST" action="controladores/login.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <?php if (isset($_SESSION['errores'])) : ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($_SESSION['errores'] as $error) : ?>
                                <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['errores']); ?>
                <?php endif; ?>


                <?php if (isset($_SESSION['exito'])) : ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $_SESSION['exito']; ?>
                    </div>
                    <?php unset($_SESSION['exito']); ?>
                <?php endif; ?>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Correo electrónico" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required>
                </div>
                <div class="options">
                    <label class="checkbox">
                        <input type="checkbox"> Recordarme
                    </label>
                    <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
                <div class="demo-info">
                    <i class="fas fa-info-circle"></i> Demo: admin@vetcare.com / cualquier contraseña
                </div>
            </form>
        </div>
    </div>
</body>
</html>