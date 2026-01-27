<?php
// Rock-Solid Pure CSS Mobile Overlay for Admin (All Items Included)
?>
<div id="customMobileMenu" class="custom-mobile-menu">
    <div class="menu-header">
        <div class="brand">
            <i class="fas fa-user-shield text-primary"></i>
            <span>SAMS ADMIN</span>
        </div>
        <button onclick="toggleCustomMenu()" class="close-btn">&times;</button>
    </div>
    
    <div class="menu-body">
        <div class="menu-section">MAIN MENU</div>
        <a href="index.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        
        <div class="menu-section">ACADEMIC</div>
        <a href="createClass.php" class="menu-item"><i class="fas fa-university"></i> <span>Departments</span></a>
        <a href="createClassArms.php" class="menu-item"><i class="fas fa-layer-group"></i> <span>Semesters</span></a>
        
        <div class="menu-section">STAFF & STUDENTS</div>
        <a href="createClassTeacher.php" class="menu-item"><i class="fas fa-chalkboard-teacher"></i> <span>Department Teachers</span></a>
        <a href="createStudents.php" class="menu-item"><i class="fas fa-user-graduate"></i> <span>Manage Students</span></a>
        
        <div class="menu-section">SETTINGS</div>
        <a href="createSessionTerm.php" class="menu-item"><i class="fas fa-cog"></i> <span>Session & Term</span></a>
        
        <div class="menu-footer">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<style>
.custom-mobile-menu {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: #0f172a !important; 
    z-index: 999999 !important;
    display: none;
    flex-direction: column;
    padding: 20px 25px !important;
    overflow-y: auto !important;
}

.custom-mobile-menu.active {
    display: flex !important;
}

.menu-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding-bottom: 25px !important;
    border-bottom: 1px solid rgba(255,255,255,0.1) !important;
    margin-bottom: 10px !important;
}

.menu-header .brand {
    color: #ffffff !important;
    font-size: 1.4rem !important;
    font-weight: 800 !important;
    display: flex !important;
    align-items: center !important;
}

.menu-header .brand i {
    margin-right: 12px !important;
}

.close-btn {
    background: transparent !important;
    border: none !important;
    color: #ffffff !important;
    font-size: 2.5rem !important;
    line-height: 1 !important;
}

.menu-section {
    color: rgba(255,255,255,0.4) !important;
    font-size: 0.75rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.15rem !important;
    margin: 30px 0 15px 5px !important;
}

.menu-item {
    display: flex !important;
    align-items: center !important;
    padding: 15px 20px !important;
    background: rgba(255,255,255,0.05) !important;
    color: #ffffff !important;
    text-decoration: none !important;
    border-radius: 12px !important;
    margin-bottom: 8px !important;
    font-weight: 500 !important;
    border: 1px solid rgba(255,255,255,0.05) !important;
}

.menu-item i {
    width: 25px !important;
    margin-right: 15px !important;
    color: #6366f1 !important;
    font-size: 1.1rem !important;
}

.menu-footer {
    margin: 40px 0 60px 0 !important;
}

.logout-btn {
    display: block !important;
    width: 100% !important;
    padding: 18px !important;
    background: #ef4444 !important;
    color: #ffffff !important;
    text-align: center !important;
    text-decoration: none !important;
    border-radius: 15px !important;
    font-weight: 800 !important;
}

.custom-mobile-menu, .custom-mobile-menu * {
    opacity: 1 !important;
    visibility: visible !important;
}
</style>

<script>
function toggleCustomMenu() {
    var menu = document.getElementById('customMobileMenu');
    if (!menu) return;
    
    // Safety: Remove any legacy sidebar classes that might cause overlap
    document.body.classList.remove('sidebar-toggled');
    document.body.classList.remove('sidebar-open');
    
    if (menu.classList.contains('active')) {
        menu.classList.remove('active');
        document.body.style.overflow = '';
    } else {
        menu.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}
</script>
