 <ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center" href="index.php">
        <div class="sidebar-brand-icon">
            <i class="fas fa-chalkboard-teacher fa-2x brand-icon-grad"></i>
        </div>
        <div class="sidebar-brand-text"><span class="brand-text-primary">SAMS</span> <span class="brand-text-grad">TEACHER</span></div>
    </a>
    
    <div class="sidebar-heading">Main Menu</div>
    <li class="nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <div class="sidebar-heading">Students</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrap2" aria-expanded="true" aria-controls="collapseBootstrap2">
            <i class="fas fa-user-graduate"></i>
            <span>Management</span>
        </a>
        <div id="collapseBootstrap2" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded-xl" style="background: rgba(255,255,255,0.08); margin: 0 1rem; border: 1px solid rgba(255,255,255,0.1); padding: 0.5rem;">
                <a class="collapse-item text-muted py-2 px-3 d-block transition-all" href="viewStudents.php" style="border-radius: 10px;">View Students</a>
                <a class="collapse-item text-muted py-2 px-3 d-block transition-all" href="createSubject.php" style="border-radius: 10px;">Create Subject</a>
                <a class="collapse-item text-muted py-2 px-3 d-block transition-all" href="todaySubject.php" style="border-radius: 10px;">Today's Subject</a>
                <a class="collapse-item text-muted py-2 px-3 d-block transition-all" href="generateQrCodes.php" style="border-radius: 10px;">QR Codes</a>
            </div>
        </div>
    </li>
    <div class="sidebar-heading">Attendance</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrapcon" aria-expanded="true" aria-controls="collapseBootstrapcon">
            <i class="fa fa-calendar-alt"></i>
            <span>Operations</span>
        </a>
        <div id="collapseBootstrapcon" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded-xl" style="background: rgba(255,255,255,0.08); margin: 0 1rem; border: 1px solid rgba(255,255,255,0.1); padding: 0.5rem;">
                <a class="collapse-item text-muted py-2 px-3 d-block transition-all" href="viewAttendance.php" style="border-radius: 10px;">Class Records</a>
                <a class="collapse-item text-muted py-2 px-3 d-block transition-all" href="viewStudentAttendance.php" style="border-radius: 10px;">Student Records</a>
                <a class="collapse-item text-muted py-2 px-3 d-block transition-all" href="todayReport.php" style="border-radius: 10px;">Today's Report</a>
            </div>
        </div>
    </li>
    <hr class="sidebar-divider d-none d-md-block opacity-10">
    <div class="text-center d-none d-md-inline mt-3">
        <button class="rounded-circle border-0" id="sidebarToggle" style="background: var(--sidebar-hover); color: #fff; width: 35px; height: 35px;"></button>
    </div>
</ul>

<style>
    .collapse-item:hover {
        background: var(--sidebar-accent-gradient) !important;
        color: #fff !important;
        text-decoration: none;
        transform: translateX(5px) scale(1.02);
    }
    .collapse-item.active {
        color: var(--primary) !important;
        font-weight: 700;
    }
</style>