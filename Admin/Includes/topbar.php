<?php 

  $fullName = "Admin"; // default fallback
  $profileImg = "img/user-icn.png";

  if (isset($_SESSION['userId'])) {
    $adminId = intval($_SESSION['userId']);
    if ($adminId > 0) {
      // Find profile image
      $dir = __DIR__ . '/../uploads';
      $base = 'admin_' . $adminId;
      foreach (['jpg','png','jpeg','webp'] as $ext) {
        if (file_exists($dir . '/' . $base . '.' . $ext)) {
          $profileImg = 'uploads/' . $base . '.' . $ext . '?v=' . time();
          break;
        }
      }

      $query = "SELECT * FROM tbladmin WHERE Id = ".$adminId;
      $rs = $conn->query($query);
      if ($rs && $rs->num_rows > 0) {
        $rows = $rs->fetch_assoc();
        if ($rows) {
          $f = $rows['firstName'] ?? '';
          $l = $rows['lastName'] ?? '';
          if ($f !== '' || $l !== '') {
            $fullName = trim($f . " " . $l);
          } else {
            $fullName = $rows['emailAddress'] ?? 'Admin';
          }
        }
      }
    }
  }

?>
<nav class="navbar navbar-expand navbar-light topbar mb-4 static-top">
  <div class="d-flex align-items-center">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
      <i class="fa fa-bars"></i>
    </button>
    <a href="index.php" class="topbar-brand-link d-flex flex-column justify-content-center text-decoration-none">
      <h4 class="font-weight-bold mb-0" style="font-size: 1rem; letter-spacing: -0.5px; line-height: 1.2;"><span style="color: #6366f1; font-weight: 800;">SAMS</span> <span style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700;">Admin</span></h4>
    </a>
  </div>

  <ul class="navbar-nav ml-auto align-items-center">
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
        <div class="user-profile-container d-flex align-items-center p-2 transition-all">
          <div class="text-right mr-3">
            <span class="d-block font-weight-bold" style="color: var(--text-primary); font-size: 0.9rem; line-height: 1.1;"><?php echo $fullName;?></span>
            <span class="d-block text-muted" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Active</span>
          </div>
          <div class="position-relative profile-icon-wrapper" style="width: 44px; height: 44px; padding: 2px; background: var(--primary-gradient); border-radius: 50%; box-shadow: 0 4px 12px var(--primary-glow);">
            <img class="img-profile rounded-circle" src="<?php echo $profileImg; ?>" style="width: 100%; height: 100%; border: 2px solid #fff; object-fit: cover;">
            <div class="status-indicator bg-success" style="position: absolute; bottom: 2px; right: 2px; width: 11px; height: 11px; border-radius: 50%; border: 2px solid #fff; z-index: 10;"></div>
          </div>
        </div>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow border-0 animated--fade-in" aria-labelledby="userDropdown" 
           style="border-radius: 12px; padding: 0.5rem; margin-top: 15px; min-width: 210px; box-shadow: 0 10px 40px rgba(0,0,0,0.1) !important;">
        <div class="dropdown-header text-muted small text-uppercase font-weight-bold mb-1">User Options</div>
        <a class="dropdown-item py-2 d-flex align-items-center" href="profile.php" style="border-radius: 8px; color: var(--text-primary); font-weight: 500;">
          <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background: rgba(99, 102, 241, 0.1); border-radius: 8px;">
            <i class="fas fa-user-circle fa-sm text-primary"></i>
          </div>
          <span>My Profile</span>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item py-2 d-flex align-items-center" href="logout.php" style="border-radius: 8px; color: var(--text-primary); font-weight: 500;">
          <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background: rgba(239, 68, 68, 0.1); border-radius: 8px;">
            <i class="fas fa-sign-out-alt fa-sm text-danger"></i>
          </div>
          <span>Logout</span>
        </a>
      </div>
    </li>
  </ul>
</nav>
<!-- Sidebar Backdrop for Mobile -->
<div class="sidebar-mask" id="sidebarMask"></div>

<style>
  .user-profile-container {
    border: 1px solid transparent;
    border-radius: 12px;
  }
  .user-profile-container:hover {
    background: var(--bg-main);
    border-color: var(--card-border);
  }
  .dropdown-item:hover {
    background: var(--bg-main) !important;
    transform: translateX(4px);
  }
</style>

<script>
  (function(){
    function ready(fn){
      if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn);}else{fn();}
    }
    ready(function(){
      var btn = document.getElementById('appBackBtn');
      if(btn) {
        btn.addEventListener('click', function(e){
          var ref = document.referrer || '';
          if(!ref) return;
          try {
            if (ref.indexOf(window.location.origin) === 0) {
              e.preventDefault();
              window.history.back();
            }
          } catch (err) {}
        });
      }

      // Pro Mobile Sidebar Logic - works with ruang-admin.js
      var toggleBtn = document.getElementById('sidebarToggleTop');
      var mask = document.getElementById('sidebarMask');
      var body = document.body;

      function isSidebarOpen() {
        return body.classList.contains('sidebar-toggled') || body.classList.contains('sidebar-open');
      }

      function closeSidebar() {
        body.classList.remove('sidebar-open');
        body.classList.remove('sidebar-toggled');
        var sidebar = document.querySelector('.sidebar');
        if(sidebar) {
          sidebar.classList.add('toggled');
        }
      }

      // Toggle button syncs sidebar-open class for our custom CSS
      if(toggleBtn) {
        // Use capture phase to intercept the click before it reaches ruang-admin.js
        toggleBtn.addEventListener('click', function(e) {
          if(window.innerWidth <= 768) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Kill the original theme script!
            e.stopPropagation();
            
            // Remove any classes that ruang-admin.js might have added
            document.body.classList.remove('sidebar-toggled');
            document.body.classList.remove('sidebar-open');
            
            // Open the rock-solid custom overlay menu
            toggleCustomMenu();
            return false;
          }
        }, true); // UseCapture = true
      }
      
      if(mask) {
        // Close sidebar when clicking/touching the mask
        mask.addEventListener('click', function(e) {
          e.stopPropagation();
          closeSidebar();
        });
        mask.addEventListener('touchend', function(e) {
          e.preventDefault();
          e.stopPropagation();
          closeSidebar();
        });
      }

      // Also close sidebar when clicking on main content wrapper
      var contentWrapper = document.getElementById('content-wrapper');
      if(contentWrapper) {
        contentWrapper.addEventListener('click', function(e) {
          // Only close if sidebar is open and on mobile
          if(isSidebarOpen() && window.innerWidth <= 768) {
            if(!e.target.closest('a, button, input, select, .btn, .nav-item')) {
              closeSidebar();
            }
          }
        });
      }
    });
  })();
</script>