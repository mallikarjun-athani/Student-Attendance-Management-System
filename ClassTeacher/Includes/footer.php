<!-- Pro Mobile Bottom Navigation -->
<nav class="mobile-nav">
  <a href="index.php" class="mobile-nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
    <i class="fas fa-home"></i>
    <span>Home</span>
  </a>
  <a href="viewStudents.php" class="mobile-nav-item <?php echo ($currentPage == 'viewStudents.php') ? 'active' : ''; ?>">
    <i class="fas fa-users"></i>
    <span>Students</span>
  </a>
  <a href="viewAttendance.php" class="mobile-nav-item <?php echo ($currentPage == 'viewAttendance.php') ? 'active' : ''; ?>">
    <i class="fas fa-calendar-check"></i>
    <span>Attendance</span>
  </a>
  <a href="todayReport.php" class="mobile-nav-item <?php echo ($currentPage == 'todayReport.php') ? 'active' : ''; ?>">
    <i class="fas fa-file-alt"></i>
    <span>Reports</span>
  </a>
  <a href="#" class="mobile-nav-item" data-toggle="modal" data-target="#mobileMenuModal">
    <i class="fas fa-bars"></i>
    <span>Menu</span>
  </a>
</nav>

<?php include 'Includes/mobile-menu.php'; ?>


<footer class="sticky-footer" style="background: #f8fafc; padding: 1.5rem 0; border-top: 1px solid #e2e8f0;">
        <div class="container my-auto">
          <div class="copyright text-center my-auto">
            <span> &copy; <script> document.write(new Date().getFullYear()); </script> 
            </span>
          </div>
        </div>
      </footer>

<div class="app-sidebar-backdrop" id="appSidebarBackdrop" aria-hidden="true"></div>

<div id="appToastHost" class="app-toast-host" aria-live="polite" aria-atomic="true"></div>

<style>
  .app-toast-host{position:fixed;top:16px;right:16px;z-index:1060;display:flex;flex-direction:column;gap:10px;max-width:360px}
  .app-toast{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:12px;box-shadow:0 10px 30px rgba(17,24,39,.18);background:#fff;border:1px solid rgba(229,231,235,.95);color:#111827;min-width:260px;max-width:360px}
  .app-toast__bar{width:4px;border-radius:999px;flex:0 0 4px;align-self:stretch}
  .app-toast__body{flex:1 1 auto;line-height:1.25;word-break:break-word}
  .app-toast__title{font-weight:700;margin:0 0 2px 0;font-size:14px}
  .app-toast__msg{margin:0;font-size:14px}
  .app-toast__close{border:0;background:transparent;color:#6b7280;line-height:1;padding:2px 6px;font-size:18px}
  .app-toast--success .app-toast__bar{background:#16a34a}
  .app-toast--danger .app-toast__bar{background:#dc2626}
  .app-toast--warning .app-toast__bar{background:#d97706}
  .app-toast--info .app-toast__bar{background:#2563eb}
  @media (max-width: 576px){
    .app-toast-host{left:12px;right:12px;top:12px;max-width:none}
    .app-toast{min-width:0;max-width:none;width:100%}
  }
</style>

<script>
  (function(){
    function getToastHost(){
      var host = document.getElementById('appToastHost');
      if (!host) {
        host = document.createElement('div');
        host.id = 'appToastHost';
        host.className = 'app-toast-host';
        document.body.appendChild(host);
      }
      return host;
    }

    function mapAlertClassToType(className){
      var s = String(className || '');
      if (s.indexOf('alert-success') >= 0) return 'success';
      if (s.indexOf('alert-danger') >= 0) return 'danger';
      if (s.indexOf('alert-warning') >= 0) return 'warning';
      return 'info';
    }

    window.showToast = function(message, type, title){
      var host = getToastHost();
      var toast = document.createElement('div');
      var t = type || 'info';
      toast.className = 'app-toast app-toast--' + t;
      toast.setAttribute('role', 'status');

      var safeTitle = title || (t === 'success' ? 'Success' : (t === 'danger' ? 'Error' : (t === 'warning' ? 'Warning' : 'Info')));

      toast.innerHTML =
        '<div class="app-toast__bar"></div>' +
        '<div class="app-toast__body">' +
          '<div class="app-toast__title"></div>' +
          '<p class="app-toast__msg"></p>' +
        '</div>' +
        '<button type="button" class="app-toast__close" aria-label="Close">&times;</button>';

      toast.querySelector('.app-toast__title').textContent = safeTitle;
      toast.querySelector('.app-toast__msg').textContent = String(message || '');

      var closeBtn = toast.querySelector('.app-toast__close');
      var timer = setTimeout(function(){
        if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
      }, 6000);

      closeBtn.addEventListener('click', function(){
        clearTimeout(timer);
        if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
      });

      host.appendChild(toast);
      return toast;
    };

    document.addEventListener('DOMContentLoaded', function(){
      var candidates = document.querySelectorAll('.alert[data-toast="1"], .alert[style*="margin-right:700px"], .alert[style*="margin-right: 700px"]');
      if (!candidates || candidates.length === 0) return;
      candidates.forEach(function(el){
        try {
          var type = mapAlertClassToType(el.className);
          var msg = (el.textContent || '').trim();
          if (msg) window.showToast(msg, type);
          el.style.display = 'none';
        } catch (e) {}
      });
    });

    document.addEventListener('DOMContentLoaded', function(){
      var backdrop = document.getElementById('appSidebarBackdrop');
      if (!backdrop) return;

      function closeSidebar(){
        document.body.classList.remove('sidebar-toggled');
      }

      backdrop.addEventListener('click', function(){
        closeSidebar();
      });

      document.addEventListener('keydown', function(e){
        if (e && e.key === 'Escape') closeSidebar();
      });
    });
  })();
</script>