<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetCare | Bienvenido a tu Clínica Veterinaria</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9f0 0%, #e6f0f5 100%);
            min-height: 100vh;
            color: #1e2a3e;
        }

        /* Navbar */
        .navbar {
            background: white;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo i {
            font-size: 32px;
            color: #6bcb77;
        }

        .logo h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #1f3e4b;
        }

        .logo span {
            color: #6bcb77;
        }

        .nav-buttons {
            display: flex;
            gap: 16px;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #6bcb77;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            color: #2c5a6e;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-outline:hover {
            background: #6bcb77;
            color: white;
            border-color: #6bcb77;
        }

        .btn-primary {
            background: #6bcb77;
            border: none;
            padding: 10px 28px;
            border-radius: 40px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            background: #54b561;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(107, 203, 119, 0.3);
        }

        /* Hero Section */
        .hero {
            max-width: 1300px;
            margin: 0 auto;
            padding: 60px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .hero-content h2 {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #1f3e4b;
        }

        .hero-content .highlight {
            color: #6bcb77;
        }

        .hero-content p {
            font-size: 1.1rem;
            color: #4a6272;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .btn-large {
            padding: 14px 36px;
            font-size: 1rem;
        }

        .hero-image {
            background: linear-gradient(145deg, #d9f0e0, #c8e2d0);
            border-radius: 50px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.1);
        }

        .hero-image i {
            font-size: 180px;
            color: #6bcb77;
            filter: drop-shadow(8px 12px 15px rgba(0,0,0,0.1));
        }

        /* Features Section */
        .features {
            background: white;
            padding: 70px 40px;
            text-align: center;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 50px;
            color: #1f3e4b;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 35px;
        }

        .feature-card {
            padding: 30px 20px;
            border-radius: 30px;
            background: #fefefe;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #eef2f0;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px rgba(107, 203, 119, 0.15);
            border-color: #6bcb77;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: #eef7f0;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .feature-icon i {
            font-size: 40px;
            color: #6bcb77;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .feature-card p {
            color: #5f7f8f;
            line-height: 1.5;
        }

        /* Footer */
        footer {
            background: #1a2f3a;
            color: #bcd0da;
            text-align: center;
            padding: 30px;
            font-size: 0.85rem;
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 40px 20px;
            }
            .hero-buttons {
                justify-content: center;
            }
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            .hero-content h2 {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-paw"></i>
            <h1>Vet<span>Care</span></h1>
        </div>
        <div class="nav-buttons">
            <button class="btn-outline" onclick="window.location.href='login.php'">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </div>
    </nav>

    <div class="hero">
        <div class="hero-content">
            <h2>Cuidado y amor para <span class="highlight">tu mejor amigo</span></h2>
            <p>Gestión completa para clínicas veterinarias. Agenda citas, controla historiales médicos y brinda la mejor atención. Todo en un solo lugar.</p>
            <div class="hero-buttons">
                <button class="btn-primary btn-large" onclick="window.location.href='registro.php'">
                    Comienza gratis <i class="fas fa-arrow-right"></i>
                </button>
                <button class="btn-outline btn-large" onclick="window.location.href='login.php'">
                    Ya tengo cuenta
                </button>
            </div>
        </div>
        <div class="hero-image">
            <i class="fas fa-dog"></i>
            <i class="fas fa-cat" style="font-size: 140px; margin-left: 20px;"></i>
        </div>
    </div>

    <div class="features">
        <h2 class="section-title">¿Por qué elegir VetCare?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                <h3>Gestión de Citas</h3>
                <p>Organiza horarios, recibe recordatorios automáticos y optimiza tu agenda.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-notes-medical"></i></div>
                <h3>Expedientes Digitales</h3>
                <p>Historial clínico completo de cada paciente, vacunas y tratamientos.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Reportes Inteligentes</h3>
                <p>Análisis de ingresos, pacientes frecuentes y métricas clave.</p>
            </div>
        </div>
    </div>

    <footer>
        <p><i class="fas fa-paw"></i> VetCare - Sistema de Gestión Veterinaria | © 2025 Cuidando a quienes amas</p>
    </footer>
</body>
</html>