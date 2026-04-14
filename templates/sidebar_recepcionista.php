<div class="top-navbar">
    <div class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </div>
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar paciente, cita...">
    </div>
    <div class="user-info">
        <div class="notification-bell">
            <i class="far fa-bell"></i>
            <div class="notification-dropdown">
                <div class="notif-header">
                    <h4>Notificaciones</h4>
                </div>
                <div class="notif-list">
                    <!-- Se cargará dinámicamente -->
                </div>
            </div>
        </div>
        <div class="avatar">
            <span><?php echo htmlspecialchars(substr($_SESSION['nombre'], 0, 2)); ?></span>
        </div>
        <div class="user-details">
            <span style="font-weight:500; font-size:0.85rem;"><?= htmlspecialchars(ucfirst($_SESSION['nombre'])) ?></span>
            <span class="user-role"><?php echo htmlspecialchars(ucfirst($_SESSION['rol'])); ?></span>
        </div>
    </div>
</div>