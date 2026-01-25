
<?php 

  $fullName = 'User';
  $profileImg = 'img/user-icn.png';
  if (isset($_SESSION['userId']) && $_SESSION['userId'] !== '') {
    $userId = intval($_SESSION['userId']);
    $base = 'uploads/teacher_' . $userId;
    if (file_exists(__DIR__ . '/../' . $base . '.jpg')) {
      $profileImg = $base . '.jpg';
    } else if (file_exists(__DIR__ . '/../' . $base . '.png')) {
      $profileImg = $base . '.png';
    } else if (file_exists(__DIR__ . '/../' . $base . '.webp')) {
      $profileImg = $base . '.webp';
    }

    $query = "SELECT * FROM tblclassteacher WHERE Id = ".$userId;
    $rs = $conn->query($query);
    if ($rs && $rs->num_rows > 0) {
      $rows = $rs->fetch_assoc();
      if ($rows && isset($rows['firstName']) && isset($rows['lastName'])) {
        $fullName = $rows['firstName']." ".$rows['lastName'];
      }
    }
  }

?>
<nav class="navbar navbar-expand navbar-light topbar mb-4 static-top">
  <div class="d-flex align-items-center">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
      <i class="fa fa-bars"></i>
    </button>
    <div class="ml-2 d-flex flex-column justify-content-center">
      <h4 class="font-weight-bold mb-0" style="color: var(--text-primary); font-size: 1rem; letter-spacing: -0.5px; line-height: 1.2;"><span class="brand-text-primary">SAMS</span> <span class="brand-text-grad">Teacher</span></h4>
    </div>
  </div>

  <ul class="navbar-nav ml-auto align-items-center">
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <div class="user-profile-container d-flex align-items-center p-2 transition-all">
          <div class="text-right mr-3">
            <span class="d-block font-weight-bold" style="color: var(--text-primary); font-size: 0.9rem; line-height: 1.1;"><?php echo $fullName;?></span>
            <span class="d-block text-muted" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Teacher</span>
          </div>
          <div class="position-relative profile-icon-wrapper" style="width: 44px; height: 44px; padding: 2px; background: var(--primary-gradient); border-radius: 50%; box-shadow: 0 4px 12px var(--primary-glow);">
            <img class="img-profile rounded-circle" id="teacherAvatarImg" src="<?php echo htmlspecialchars($profileImg); ?>" style="width: 100%; height: 100%; border: 2px solid #fff; object-fit: cover; cursor:pointer;">
            <div class="status-indicator bg-success" style="position: absolute; bottom: 2px; right: 2px; width: 11px; height: 11px; border-radius: 50%; border: 2px solid #fff; z-index: 10;"></div>
          </div>
        </div>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow border-0 animated--fade-in" aria-labelledby="userDropdown" style="border-radius: 12px; padding: 0.5rem; margin-top: 15px; min-width: 210px; box-shadow: 0 10px 40px rgba(0,0,0,0.1) !important;">
        <div class="dropdown-header text-muted small text-uppercase font-weight-bold mb-1">Teacher Options</div>
        <input type="file" id="teacherAvatarInput" accept="image/*" style="display:none;">
        <label class="dropdown-item py-2 d-flex align-items-center" for="teacherAvatarInput" style="border-radius: 8px; color: var(--text-primary); font-weight: 500; cursor:pointer; margin-bottom: 2px;">
          <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background: rgba(78, 102, 241, 0.1); border-radius: 8px;">
            <i class="fas fa-camera fa-sm text-primary"></i>
          </div>
          <span>Update Photo</span>
        </label>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item py-2 d-flex align-items-center" href="logout.php" style="border-radius: 8px; color: var(--text-primary); font-weight: 500;">
          <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background: rgba(239, 68, 68, 0.1); border-radius: 8px;">
            <i class="fas fa-power-off fa-sm text-danger"></i>
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

      // Pro Mobile Sidebar Toggle Logic
      var toggleBtn = document.getElementById('sidebarToggleTop');
      var mask = document.getElementById('sidebarMask');
      var body = document.body;

      if(toggleBtn && mask) {
        toggleBtn.addEventListener('click', function() {
          body.classList.toggle('sidebar-open');
        });
        mask.addEventListener('click', function() {
          body.classList.remove('sidebar-open');
          var sidebar = document.querySelector('.sidebar');
          if(sidebar && sidebar.classList.contains('toggled')) {
            toggleBtn.click();
          }
        });
      }
    });
  })();
</script>

<script>
  (function(){
    function ready(fn){
      if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn);}else{fn();}
    }
    ready(function(){
      var img = document.getElementById('teacherAvatarImg');
      var input = document.getElementById('teacherAvatarInput');
      if(!img || !input) return;

      img.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        input.click();
      });

      input.addEventListener('change', function(){
        if(!input.files || !input.files[0]) return;
        var file = input.files[0];

        var form = new FormData();
        form.append('photo', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'uploadProfilePhoto.php', true);
        xhr.onload = function(){
          try {
            var res = JSON.parse(xhr.responseText || '{}');
            if (xhr.status >= 200 && xhr.status < 300 && res.ok && res.url) {
              img.src = res.url;
            } else {
              alert((res && res.message) ? res.message : 'Upload failed.');
            }
          } catch (e) {
            alert('Upload failed.');
          }
          input.value = '';
        };
        xhr.onerror = function(){
          alert('Upload failed.');
          input.value = '';
        };
        xhr.send(form);
      });
    });
  })();
</script>