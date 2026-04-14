<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - VetCare</title>
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
            background: linear-gradient(145deg, #f8fafc, #eef2f5);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            max-width: 550px;
            background: white;
            padding: 50px 40px;
            border-radius: 50px;
            box-shadow: 0 25px 45px rgba(0,0,0,0.1);
        }
        .error-icon {
            font-size: 100px;
            color: #e67e22;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 5rem;
            font-weight: 800;
            color: #c0392b;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        p {
            color: #5d7a8c;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-home {
            background: #6bcb77;
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        .btn-home:hover {
            background: #54b561;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-ban"></i>
        </div>
        <h1>403</h1>
        <h2>Acceso Denegado</h2>
        <p>No tienes permisos suficientes para acceder a esta área. Por favor contacta al administrador si crees que esto es un error.</p>
        <a href="index.html" class="btn-home"><i class="fas fa-home"></i> Volver al inicio</a>
        <div style="margin-top: 25px;">
            <a href="login.html" style="color:#6bcb77; text-decoration:none;">Iniciar sesión con otra cuenta</a>
        </div>
    </div>
</body>
</html>