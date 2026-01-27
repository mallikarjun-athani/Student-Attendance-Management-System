 <ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center" href="index.php">
        <div class="sidebar-brand-icon">
            <i class="fas fa-user-shield fa-2x brand-icon-grad"></i>
        </div>
        <div class="sidebar-brand-text"><span class="brand-text-primary">SAMS</span> <span class="brand-text-grad">ADMIN</span></div>
    </a>
    
    <div class="sidebar-heading">Main Menu</div>
    <li class="nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="index.php">
            <div class="nav-icon-wrapper">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <span>Dashboard</span>
        </a>
    </li>

    <div class="sidebar-heading">Operations</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseClass" aria-expanded="true" aria-controls="collapseClass">
            <div class="nav-icon-wrapper">
                <i class="fas fa-university"></i>
            </div>
            <span>Academic</span>
        </a>
        <div id="collapseClass" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
            <div class="collapse-inner">
                <a class="collapse-item" href="createClass.php">Departments</a>
                <a class="collapse-item" href="createClassArms.php">Semesters</a>
            </div>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTeacher" aria-expanded="true" aria-controls="collapseTeacher">
            <div class="nav-icon-wrapper">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <span>Staff</span>
        </a>
        <div id="collapseTeacher" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
            <div class="collapse-inner">
                <a class="collapse-item" href="createClassTeacher.php">Teachers</a>
            </div>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStudent" aria-expanded="true" aria-controls="collapseStudent">
            <div class="nav-icon-wrapper">
                <i class="fas fa-user-graduate"></i>
            </div>
            <span>Students</span>
        </a>
        <div id="collapseStudent" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
            <div class="collapse-inner">
                <a class="collapse-item" href="createStudents.php">Manage</a>
            </div>
        </div>
    </li>

    <!-- Removed Session & Term Section as per Request -->

    <!-- <hr class="sidebar-divider d-none d-md-block opacity-10"> -->

    <div class="text-center d-none d-md-inline mt-4 px-3">
        <button class="rounded-circle border-0 sidebar-toggle-btn" id="sidebarToggle"></button>
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