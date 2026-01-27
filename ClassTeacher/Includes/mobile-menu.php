<?php
// Mobile Full Screen Overlay Menu for Teacher
?>
<div class="modal fade" id="mobileMenuModal" tabindex="-1" role="dialog" aria-labelledby="mobileMenuModalLabel" aria-hidden="true" style="padding-right: 0px !important;">
    <div class="modal-dialog modal-fullscreen m-0" role="document" style="max-width: 100%; margin: 0; min-height: 100vh;">
        <div class="modal-content border-0" style="border-radius: 0; background: var(--sidebar-bg); min-height: 100vh;">
            <div class="modal-header border-0 pb-0" style="padding: 1.5rem 1.5rem 0.5rem 1.5rem;">
                <div class="sidebar-brand d-flex align-items-center">
                    <div class="sidebar-brand-icon">
                        <i class="fas fa-chalkboard-teacher fa-2x brand-icon-grad"></i>
                    </div>
                    <div class="sidebar-brand-text ml-2"><span class="brand-text-primary">SAMS</span> <span class="brand-text-grad">TEACHER</span></div>
                </div>
                <button type="button" class="close text-white opacity-100" data-dismiss="modal" aria-label="Close" style="font-size: 2.5rem; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="sidebar-heading text-white-50 small font-weight-bold text-uppercase mb-3" style="letter-spacing: 0.1rem;">Main Menu</div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-xl mb-2 <?php echo ($currentPage == 'index.php') ? 'bg-white-10' : ''; ?>">
                        <i class="fas fa-fw fa-home mr-3"></i> <span>Dashboard</span>
                    </a>
                </div>

                <div class="sidebar-heading text-white-50 small font-weight-bold text-uppercase mt-4 mb-3" style="letter-spacing: 0.1rem;">Class Info</div>
                <div class="list-group list-group-flush">
                    <a href="viewStudents.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-xl mb-2">
                        <i class="fas fa-fw fa-users mr-3"></i> <span>Manage Students</span>
                    </a>
                    <a href="takeAttendance.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-xl mb-2">
                        <i class="fas fa-fw fa-check-circle mr-3"></i> <span>Take Attendance</span>
                    </a>
                    <a href="viewAttendance.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-xl mb-2">
                        <i class="fas fa-fw fa-calendar-alt mr-3"></i> <span>View Attendance</span>
                    </a>
                    <a href="todayReport.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-xl mb-2">
                        <i class="fas fa-fw fa-file-invoice mr-3"></i> <span>Today's Report</span>
                    </a>
                </div>
                
                <div class="mt-5 pt-5 border-top border-white-10">
                   <a href="logout.php" class="btn btn-danger btn-block py-3 rounded-pill shadow-lg">
                       <i class="fas fa-sign-out-alt mr-2"></i> Log Out
                   </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #mobileMenuModal .modal-content {
        animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp {
        from { transform: translateY(100vh); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .bg-white-10 {
        background: rgba(255, 255, 255, 0.1) !important;
    }
    .rounded-xl {
        border-radius: 12px !important;
    }
    .border-white-10 {
        border-color: rgba(255,255,255,0.1) !important;
    }
</style>
