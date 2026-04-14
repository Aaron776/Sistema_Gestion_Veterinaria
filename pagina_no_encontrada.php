<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página No Encontrada - VetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f1f9fe, #e2eef7);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .notfound-container {
            text-align: center;
            background: white;
            padding: 55px 40px;
            border-radius: 60px;
            max-width: 600px;
            box-shadow: 0 30px 50px rgba(0,0,0,0.08);
        }
        .notfound-icon {
            font-size: 110px;
            color: #f39c12;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 6rem;
            font-weight: 800;
            color: #3498db;
            letter-spacing: 5px;
        }
        h3 {
            font-size: 1.6rem;
            margin: 15px 0 10px;
            color: #2c3e50;
        }
        p {
            color: #6c8d9e;
            margin: 20px 0 30px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: #6bcb77;
            color: white;
            padding: 12px 28px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary {
            background: #eef2f5;
            color: #2c5a6e;
            padding: 12px 28px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #54b561;
            transform: translateY(-2px);
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="notfound-container">
        <div class="notfound-icon">
            <i class="fas fa-dog"></i>
            <i class="fas fa-question-circle"></i>
        </div>
        <h1>404</h1>
        <h3>¡Vaya! Página no encontrada</h3>
        <p>La página que estás buscando pudo haber sido movida, eliminada o quizás nunca existió. Pero no te preocupes, podemos ayudarte a volver al camino.</p>
        <div class="btn-group">
            <a href="index.html" class="btn-primary"><i class="fas fa-home"></i> Ir al inicio</a>
            <a href="dashboard.html" class="btn-secondary"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </div>
        <div style="margin-top: 30px;">
            <a href="login.html" style="color:#6bcb77;">Iniciar sesión</a> | 
            <a href="registro.html" style="color:#6bcb77;">Crear cuenta</a>
        </div>
    </div>
</body>
</html>