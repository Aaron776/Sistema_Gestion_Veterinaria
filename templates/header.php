<?php
// Comprobar el estado actual de la sesión
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../conexion/session.php';
}

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit;
}

// Calcular la ruta base relativa hacia la raíz del proyecto
// Obtenemos el archivo que incluye este header
$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
$including_file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : __FILE__;
$including_dir = dirname($including_file);
$root_dir = dirname(__DIR__); // Directorio raíz del proyecto

// Normalizar las rutas para que funcionen en Windows y Linux
$including_dir = str_replace('\\', '/', $including_dir);
$root_dir = str_replace('\\', '/', $root_dir);

// Calcular la ruta relativa desde el directorio del archivo que incluye el header hacia la raíz
$relative_path = str_replace($root_dir, '', $including_dir);
$relative_path = trim($relative_path, '/');
$depth = !empty($relative_path) ? substr_count($relative_path, '/') + 1 : 0;

// Construir la ruta base: si está en bodeguero/ o cajero/ o admin/, necesitamos "../", si está en la raíz, ""
$base_url = $depth > 0 ? str_repeat('../', $depth) : '';

// Obtener la página actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF']);

// Función para verificar si el enlace está activo
function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>VetCare | Dashboard Clínica Veterinaria</title>
    <!-- Google Fonts: Inter & Poppins para estilo moderno -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 (Free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js CDN para gráficas modernas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            overflow-x: hidden;
            color: #1e2a3e;
        }

        /* === Layout principal estilo AdminLTE pero modernizado === */
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ========= SIDEBAR ========= */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f2b3d 0%, #1a3a4f 100%);
            color: #e2f0f7;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
        }

        .sidebar-header {
            padding: 28px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-header i {
            font-size: 32px;
            color: #6bcb77;
        }

        .sidebar-header h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.6rem;
            letter-spacing: -0.3px;
            color: white;
        }

        .sidebar-header span {
            font-size: 0.75rem;
            background: #6bcb77;
            padding: 2px 8px;
            border-radius: 30px;
            margin-left: 5px;
            color: #0f2b3d;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 20px 0 30px;
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

        .menu-item {
            padding: 12px 24px;
            margin: 6px 12px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #cbdbe2;
            transition: 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .menu-item i {
            width: 26px;
            font-size: 1.25rem;
        }

        .menu-item span {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .sidebar-menu a.menu-item {
            text-decoration: none;
            color: #cbdbe2;
            padding: 12px 24px;
            margin: 6px 12px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: 0.2s;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: transparent;
        }

        .sidebar-menu a.menu-item:link,
        .sidebar-menu a.menu-item:visited,
        .sidebar-menu a.menu-item:focus,
        .sidebar-menu a.menu-item:hover,
        .sidebar-menu a.menu-item:active {
            text-decoration: none;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: transparent;
            color: inherit;
        }

        .sidebar-menu a.menu-item:hover {
            background: rgba(107, 203, 119, 0.2);
            color: white;
        }

        .sidebar-menu a.menu-item.active {
            background: #6bcb77;
            color: #0f2b3d;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* ========= MENU DROPDOWN ========= */
        .menu-dropdown {
            margin: 6px 12px;
        }

        .menu-dropdown-toggle {
            padding: 12px 24px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #cbdbe2;
            transition: 0.2s;
            cursor: pointer;
        }

        .menu-dropdown-toggle:hover {
            background: rgba(107, 203, 119, 0.2);
            color: white;
        }

        .menu-dropdown-toggle .menu-item-content {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .menu-dropdown-toggle .menu-item-content i {
            width: 26px;
            font-size: 1.25rem;
        }

        .menu-dropdown-toggle .menu-item-content span {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }

        .menu-dropdown.open .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-left: 20px;
        }

        .menu-dropdown.open .dropdown-menu {
            max-height: 200px;
        }

        .dropdown-item {
            padding: 10px 24px;
            margin: 4px 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #a8c0d0;
            transition: 0.2s;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .dropdown-item:hover {
            background: rgba(107, 203, 119, 0.15);
            color: white;
        }

        .dropdown-item.active {
            background: rgba(107, 203, 119, 0.3);
            color: white;
            font-weight: 600;
        }

        .dropdown-item i {
            width: 20px;
            font-size: 0.9rem;
        }

        /* ========= MAIN CONTENT ========= */
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s;
            background: #f4f7fc;
            padding-bottom: 60px;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid #e9edf2;
            position: sticky;
            top: 0;
            z-index: 999;
            flex-wrap: wrap;
            gap: 12px;
        }

        .menu-toggle {
            display: none;
            font-size: 1.6rem;
            cursor: pointer;
            color: #2c5a6e;
            padding: 8px;
            min-width: 44px;
            min-height: 44px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .menu-toggle:hover {
            background: #f1f5f9;
        }

        .search-bar {
            background: #f1f5f9;
            padding: 8px 18px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            max-width: 300px;
            min-width: 150px;
        }

        .search-bar i {
            color: #8ba0b0;
        }

        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 0.9rem;
            width: 220px;
            min-width: 100px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-bell {
            position: relative;
            font-size: 1.3rem;
            color: #3b6f8a;
        }

        .avatar {
            width: 44px;
            height: 44px;
            background: #6bcb77;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1rem;
        }

        .user-role {
            font-size: 0.7rem;
            font-weight: 600;
            background: #d4f5dd;
            color: #1a7a2e;
            padding: 3px 10px;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        /* Dashboard content */
        .dashboard-container {
            padding: 28px 32px;
        }

        /* Cards stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 28px;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02), 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #eef2f6;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.1);
        }

        .stat-info h3 {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c8d9e;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1e2f3c;
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            background: #eef7f0;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #6bcb77;
        }

        /* Charts y tablas */
        .row-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            margin-bottom: 32px;
        }

        .card {
            background: white;
            border-radius: 28px;
            padding: 20px 24px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.02);
            border: 1px solid #eef2f8;
            transition: all 0.2s;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            border-bottom: 1px solid #eff3f8;
            padding-bottom: 12px;
        }

        .card-header h4 {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1f3e4b;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table th {
            text-align: left;
            padding: 12px 4px;
            font-weight: 600;
            font-size: 0.75rem;
            color: #6f91a2;
            text-transform: uppercase;
        }

        .appointments-table td {
            padding: 12px 4px;
            border-bottom: 1px solid #f0f3f8;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-badge {
            background: #e0f7e8;
            color: #2b7e3a;
            padding: 4px 10px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-block;
        }

        .status-badge.warning {
            background: #fff0db;
            color: #c97e00;
        }

        .btn-small {
            background: #eef2fa;
            border: none;
            padding: 6px 12px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: default;
        }

        /* lista pacientes recientes */
        .patient-list {
            list-style: none;
        }

        .patient-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f4f9;
        }

        .patient-avatar {
            width: 42px;
            height: 42px;
            background: #d9eef5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #2f6b7c;
        }

        /* responsive */
        @media (max-width: 992px) {
            .sidebar {
                left: -280px;
                position: fixed;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .dropdown-menu {
                padding-left: 15px;
            }

            .dropdown-item {
                font-size: 0.8rem;
                padding: 8px 20px;
            }

            .row-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            footer {
                left: 0;
            }

            /* Top navbar responsive */
            .top-navbar {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 12px;
            }

            .search-bar input {
                width: 150px;
            }

            .user-details {
                display: none;
            }

            .notification-dropdown {
                width: 280px;
                right: -20px;
            }
        }

        /* Breakpoint para tablets */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .stat-card {
                padding: 16px 18px;
                border-radius: 20px;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            .stat-icon {
                width: 46px;
                height: 46px;
                font-size: 1.5rem;
            }

            .dashboard-container {
                padding: 20px 16px;
            }

            .card {
                padding: 16px 18px;
                border-radius: 20px;
            }

            .card-header h4 {
                font-size: 1rem;
            }

            .search-bar {
                display: none;
            }

            .top-navbar {
                padding: 10px 14px;
            }

            .user-info {
                gap: 12px;
            }

            .avatar {
                width: 38px;
                height: 38px;
                font-size: 0.9rem;
            }

            .notification-dropdown {
                width: 260px;
                right: -10px;
            }

            footer {
                padding: 12px 16px;
                font-size: 0.7rem;
            }

            /* Touch-friendly: elementos interactivos más grandes */
            .menu-item {
                padding: 14px 24px;
                min-height: 48px;
            }

            .dropdown-item {
                padding: 10px 20px;
                min-height: 44px;
            }

            .btn-small {
                padding: 8px 14px;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 580px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .dashboard-container {
                padding: 16px 12px;
            }

            .stat-card {
                padding: 14px 16px;
                border-radius: 16px;
            }

            .stat-number {
                font-size: 1.6rem;
            }

            .stat-icon {
                width: 42px;
                height: 42px;
                font-size: 1.3rem;
            }

            .stat-info h3 {
                font-size: 0.75rem;
            }

            .top-navbar {
                padding: 8px 12px;
                justify-content: space-between;
            }

            .search-bar {
                display: none;
            }

            .user-info {
                gap: 10px;
            }

            .avatar {
                width: 34px;
                height: 34px;
                font-size: 0.85rem;
            }

            .notification-bell {
                font-size: 1.1rem;
            }

            .notification-dropdown {
                width: 90vw;
                right: 5vw;
                top: 45px;
            }

            .card {
                padding: 14px 12px;
                border-radius: 16px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .appointments-table {
                font-size: 0.8rem;
            }

            .appointments-table th,
            .appointments-table td {
                padding: 10px 2px;
            }

            footer {
                padding: 10px 12px;
                font-size: 0.65rem;
                position: relative;
                left: 0;
                right: 0;
            }

            /* Touch-friendly: elementos aún más grandes */
            .menu-item {
                padding: 16px 20px;
                min-height: 52px;
            }

            .dropdown-item {
                padding: 12px 16px;
                min-height: 48px;
            }

            .menu-toggle {
                padding: 8px;
                min-width: 44px;
                text-align: center;
            }

            .btn-small {
                padding: 10px 16px;
                font-size: 0.8rem;
            }

            .status-badge {
                padding: 5px 12px;
                font-size: 0.75rem;
            }
        }

        /* Breakpoint para pantallas muy pequeñas */
        @media (max-width: 400px) {
            .sidebar-header {
                padding: 20px 18px;
            }

            .sidebar-header h2 {
                font-size: 1.3rem;
            }

            .sidebar-header i {
                font-size: 26px;
            }

            .stats-grid {
                gap: 12px;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }

            .stat-info {
                width: 100%;
            }

            .top-navbar {
                padding: 6px 10px;
            }

            .user-role {
                display: none;
            }

            .notification-dropdown {
                width: 95vw;
                right: 2.5vw;
            }

            .notif-header h4 {
                font-size: 0.85rem;
            }

            .notif-content h5 {
                font-size: 0.8rem;
            }

            .notif-content p {
                font-size: 0.7rem;
            }
        }

        footer {
            text-align: center;
            padding: 15px 32px;
            font-size: 0.75rem;
            color: #7f9aab;
            background: #f8fbfe;
            position: fixed;
            bottom: 0;
            left: 280px;
            right: 0;
            z-index: 998;
            border-top: 1px solid #e2e8f0;
        }

        /* ============================================
           ESTILOS SWEETALERT2 PERSONALIZADOS
           ============================================ */
        .swal2-popup.swal2-modal {
            font-family: 'Inter', sans-serif !important;
            border-radius: 20px !important;
            padding: 30px !important;
        }

        .swal2-title {
            font-family: 'Poppins', sans-serif !important;
            font-size: 1.4rem !important;
            color: #1f3e4b !important;
        }

        .swal2-html-container {
            font-family: 'Inter', sans-serif !important;
        }

        /* Iconos */
        .swal2-icon.swal2-warning {
            border-color: #f39c12 !important;
            color: #f39c12 !important;
        }

        .swal2-icon.swal2-warning .swal2-icon-content {
            color: #f39c12 !important;
        }

        .swal2-icon.swal2-success {
            border-color: #6bcb77 !important;
            color: #6bcb77 !important;
        }

        .swal2-icon.swal2-success .swal2-icon-content {
            color: #6bcb77 !important;
        }

        .swal2-icon.swal2-error {
            border-color: #e74c3c !important;
            color: #e74c3c !important;
        }

        .swal2-icon.swal2-error .swal2-icon-content {
            color: #e74c3c !important;
        }

        .swal2-icon.swal2-question {
            border-color: #3498db !important;
            color: #3498db !important;
        }

        .swal2-icon.swal2-question .swal2-icon-content {
            color: #3498db !important;
        }

        .swal2-icon.swal2-info {
            border-color: #3498db !important;
            color: #3498db !important;
        }

        .swal2-icon.swal2-info .swal2-icon-content {
            color: #3498db !important;
        }

        /* Botones */
        .swal2-confirm.swal2-styled,
        .swal2-cancel.swal2-styled {
            border-radius: 40px !important;
            padding: 12px 30px !important;
            font-weight: 600 !important;
            font-family: 'Inter', sans-serif !important;
            transition: all 0.3s !important;
        }

        .swal2-confirm.swal2-styled {
            background: #6bcb77 !important;
            border: none !important;
        }

        .swal2-confirm.swal2-styled:hover {
            background: #54b561 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(107, 203, 119, 0.3) !important;
        }

        .swal2-cancel.swal2-styled {
            background: #eef2f5 !important;
            color: #2c5a6e !important;
            border: none !important;
        }

        .swal2-cancel.swal2-styled:hover {
            background: #e2e8f0 !important;
            transform: translateY(-2px) !important;
        }

        /* Botón peligro */
        .swal2-btn-danger {
            background: #e74c3c !important;
            border-radius: 40px !important;
            padding: 12px 30px !important;
            font-weight: 600 !important;
            border: none !important;
            transition: all 0.3s !important;
        }

        .swal2-btn-danger:hover {
            background: #c0392b !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3) !important;
        }

        /* Input styles */
        .swal2-input,
        .swal2-textarea,
        .swal2-select {
            border-radius: 15px !important;
            border: 2px solid #e2e8f0 !important;
            font-family: 'Inter', sans-serif !important;
            padding: 12px 18px !important;
            transition: all 0.3s !important;
        }

        .swal2-input:focus,
        .swal2-textarea:focus,
        .swal2-select:focus {
            border-color: #6bcb77 !important;
            box-shadow: 0 0 0 3px rgba(107, 203, 119, 0.1) !important;
        }

        /* Toast notifications */
        .swal2-toast {
            border-radius: 15px !important;
            font-family: 'Inter', sans-serif !important;
        }

        /* Timer progress */
        .swal2-timer-progress-bar {
            background: #6bcb77 !important;
        }

        /* Close button */
        .swal2-close {
            color: #6c8d9e !important;
        }

        .swal2-close:hover {
            color: #2c5a6e !important;
        }

        /* --- SISTEMA DE NOTIFICACIONES --- */
        .notification-bell {
            position: relative;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .notification-bell:hover {
            transform: scale(1.1);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .notification-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
            border: 1px solid #eef2f6;
            z-index: 5000;
            display: none;
            overflow: hidden;
            animation: fadeInDown 0.3s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notif-header {
            padding: 15px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-header h4 {
            font-size: 0.9rem;
            color: #1a3a4f;
            font-weight: 700;
        }

        .notif-list {
            max-height: 380px;
            overflow-y: auto;
        }

        .notif-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f7fafc;
            transition: background 0.2s;
            cursor: pointer;
            display: flex;
            gap: 12px;
        }

        .notif-item:hover {
            background: #f1f5f9;
        }

        .notif-item.unread {
            background: #f0f9ff;
        }

        .notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notif-icon.info {
            background: #e0f2fe;
            color: #0ea5e9;
        }

        .notif-icon.urgente {
            background: #fee2e2;
            color: #ef4444;
        }

        .notif-icon.cita {
            background: #f0fdf4;
            color: #22c55e;
        }

        .notif-icon.pago {
            background: #fefce8;
            color: #ca8a04;
        }

        .notif-content h5 {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .notif-content p {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.4;
        }

        .notif-time {
            font-size: 0.65rem;
            color: #94a3b8;
            margin-top: 6px;
        }

        .notif-footer {
            padding: 12px;
            text-align: center;
            border-top: 1px solid #edf2f7;
        }

        .notif-footer a {
            font-size: 0.75rem;
            color: #6bcb77;
            font-weight: 600;
            text-decoration: none;
        }

        /* Sidebar overlay para móviles */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Asegurar que el contenido principal sea scrollable en móviles */
        @media (max-width: 768px) {
            .main-content {
                padding-bottom: 80px;
            }
        }

        @media (max-width: 580px) {
            .main-content {
                padding-bottom: 100px;
            }
        }
    </style>
    <script>
        // Dropdown menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggles = document.querySelectorAll('.menu-dropdown-toggle');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = this.closest('.menu-dropdown');
                    dropdown.classList.toggle('open');
                });
            });

            const bell = document.querySelector('.notification-bell');
            const dropdown = document.querySelector('.notification-dropdown');

            if (bell) {
                bell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = dropdown.style.display === 'block';
                    dropdown.style.display = isVisible ? 'none' : 'block';

                    if (!isVisible) {
                        marcarNotificacionesLeidas();
                    }
                });
            }

            document.addEventListener('click', function(e) {
                if (dropdown && !dropdown.contains(e.target) && !bell.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

            // Cargar inicial
            cargarNotificaciones();
            // Polling cada 30 segundos
            setInterval(cargarNotificaciones, 30000);
        });

        async function cargarNotificaciones() {
            try {
                const response = await fetch('<?= $base_url ?>controladores/notificaciones/obtener.php');
                const data = await response.json();

                if (data.status === 'success') {
                    actualizarUI_Notificaciones(data);
                }
            } catch (error) {
                console.error('Error al cargar notificaciones:', error);
            }
        }

        function actualizarUI_Notificaciones(data) {
            const badge = document.querySelector('.notification-badge');
            const listContainer = document.querySelector('.notif-list');

            // Actualizar contador
            if (data.unleidas > 0) {
                if (!badge) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.innerText = data.unleidas;
                    document.querySelector('.notification-bell').appendChild(newBadge);
                } else {
                    badge.innerText = data.unleidas;
                    badge.style.display = 'flex';
                }
            } else if (badge) {
                badge.style.display = 'none';
            }

            // Actualizar listado
            if (listContainer) {
                if (data.listado.length === 0) {
                    listContainer.innerHTML = '<div style="padding: 30px; text-align: center; color: #94a3b8; font-size: 0.8rem;"><i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>No tienes notificaciones</div>';
                    return;
                }

                listContainer.innerHTML = data.listado.map(notif => `
                    <div class="notif-item ${notif.leida === 'no' ? 'unread' : ''}">
                        <div class="notif-icon ${notif.tipo}">
                            <i class="${getIconByType(notif.tipo)}"></i>
                        </div>
                        <div class="notif-content">
                            <h5>${notif.titulo}</h5>
                            <p>${notif.mensaje}</p>
                            <div class="notif-time">${formatearFechaNotif(notif.fecha_creacion)}</div>
                        </div>
                    </div>
                `).join('');
            }
        }

        function getIconByType(tipo) {
            const icons = {
                'info': 'fas fa-info-circle',
                'urgente': 'fas fa-exclamation-triangle',
                'cita': 'fas fa-calendar-alt',
                'pago': 'fas fa-money-bill-wave',
                'inventario': 'fas fa-box-open'
            };
            return icons[tipo] || 'fas fa-bell';
        }

        function formatearFechaNotif(fechaStr) {
            const fecha = new Date(fechaStr);
            return fecha.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        async function marcarNotificacionesLeidas() {
            try {
                const response = await fetch('<?= $base_url ?>controladores/notificaciones/marcar_leidas.php', {
                    method: 'POST'
                });
                const data = await response.json();
                if (data.status === 'success') {
                    const badge = document.querySelector('.notification-badge');
                    if (badge) badge.style.display = 'none';
                }
            } catch (error) {
                console.error('Error al marcar leídas:', error);
            }
        }
    </script>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-paw"></i>
                <h2>VetCare <span>Pro</span></h2>
            </div>
            <div class="sidebar-menu">
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') { ?>
                    <a href="<?= $base_url ?>admin/dash_admin.php" class="menu-item <?= isActive('dash_admin.php', $current_page) ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= $base_url ?>admin/gestion_usuarios.php" class="menu-item <?= isActive('gestion_usuarios.php', $current_page) ?>">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                    <a href="<?= $base_url ?>admin/gestion_inventario.php" class="menu-item <?= isActive('gestion_inventario.php', $current_page) ?>">
                        <i class="fas fa-box"></i>
                        <span>Inventario</span>
                    </a>
                    <a href="<?= $base_url ?>admin/reportes.php" class="menu-item <?= isActive('reportes.php', $current_page) ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Reportes</span>
                    </a>

                <?php } elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'veterinario') { ?>
                    <a href="<?= $base_url ?>veterinario/dash_veterinario.php" class="menu-item <?= isActive('dash_veterinario.php', $current_page) ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <div class="menu-dropdown">
                        <div class="menu-dropdown-toggle">
                            <div class="menu-item-content">
                                <i class="fas fa-calendar-check"></i>
                                <span>Citas</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="<?= $base_url ?>veterinario/gestion_citas_confirmadas.php" class="dropdown-item <?= isActive('gestion_citas_confirmadas.php', $current_page) && isset($_GET['estado']) && $_GET['estado'] === 'confirmadas' ? 'active' : '' ?>">
                                <i class="fas fa-clock"></i>
                                <span>Confirmadas</span>
                            </a>
                            <a href="<?= $base_url ?>veterinario/gestion_citas_realizadas.php" class="dropdown-item <?= isActive('gestion_citas_realizadas.php', $current_page) && isset($_GET['estado']) && $_GET['estado'] === 'realizadas' ? 'active' : '' ?>">
                                <i class="fas fa-check-circle"></i>
                                <span>Realizadas</span>
                            </a>
                        </div>
                    </div>
                <?php } elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'recepcionista') { ?>
                    <a href="<?= $base_url ?>recepcionista/dash_recepcionista.php" class="menu-item <?= isActive('dash_recepcionista.php', $current_page) ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= $base_url ?>recepcionista/gestion_clientes.php" class="menu-item <?= isActive('gestion_clientes.php', $current_page) ?>">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                    <a href="<?= $base_url ?>recepcionista/gestion_citas.php" class="menu-item <?= isActive('gestion_citas.php', $current_page) ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Citas</span>
                    </a>
                <?php } ?>

                <a href="<?= $base_url ?>controladores/logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Overlay para sidebar en móviles -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') { ?>
                <?php include_once 'sidebar_admin.php'; ?>
            <?php } elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'veterinario') { ?>
                <?php include_once 'sidebar_veterinario.php'; ?>
            <?php } elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'recepcionista') { ?>
                <?php include_once 'sidebar_recepcionista.php'; ?>
            <?php } ?>