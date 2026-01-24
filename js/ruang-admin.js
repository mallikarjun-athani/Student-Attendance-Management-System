(function($) {
  "use strict"; // Start of use strict

  function ensureSidebarBackdrop() {
    if (document.querySelector('.app-sidebar-backdrop')) {
      return;
    }
    var backdrop = document.createElement('div');
    backdrop.className = 'app-sidebar-backdrop';
    backdrop.addEventListener('click', function() {
      $('body').removeClass('sidebar-toggled');
      $('.sidebar').removeClass('toggled');
      $('.sidebar .collapse').collapse('hide');
    });
    document.body.appendChild(backdrop);
  }

  function ensureToastHost() {
    var host = document.querySelector('.app-toast-host');
    if (host) {
      return host;
    }
    host = document.createElement('div');
    host.className = 'app-toast-host';
    document.body.appendChild(host);
    return host;
  }

  function moveToasts() {
    var alerts = document.querySelectorAll('.alert[data-toast="1"]');
    if (!alerts || alerts.length === 0) {
      return;
    }
    var host = ensureToastHost();
    alerts.forEach(function(a) {
      a.classList.add('app-toast');
      a.style.marginRight = '';
      host.appendChild(a);
      window.setTimeout(function() {
        a.classList.add('app-toast-out');
        window.setTimeout(function() {
          if (a && a.parentNode) {
            a.parentNode.removeChild(a);
          }
        }, 200);
      }, 4200);
    });
  }

  function isSameOriginLink(a) {
    try {
      var url = new URL(a.href, window.location.href);
      return url.origin === window.location.origin;
    } catch (e) {
      return false;
    }
  }

  function shouldAnimateLink(a) {
    if (!a || !a.getAttribute) {
      return false;
    }
    var href = a.getAttribute('href') || '';
    if (!href || href === '#' || href.indexOf('#') === 0) {
      return false;
    }
    if (a.getAttribute('target') === '_blank') {
      return false;
    }
    if (a.hasAttribute('download')) {
      return false;
    }
    if (!isSameOriginLink(a)) {
      return false;
    }
    return /\.php(\?.*)?$/.test(href) || href.indexOf('.php?') !== -1;
  }

  function animatePageIn() {
    var el = document.getElementById('container-wrapper');
    if (!el) {
      return;
    }
    el.classList.add('app-page-enter');
    window.requestAnimationFrame(function() {
      el.classList.add('app-page-enter-active');
      window.setTimeout(function() {
        el.classList.remove('app-page-enter');
        el.classList.remove('app-page-enter-active');
      }, 220);
    });
  }

  function installPageTransitions() {
    var el = document.getElementById('container-wrapper');
    if (!el) {
      return;
    }

    $(document).on('click', 'a', function(e) {
      var a = this;
      if (!shouldAnimateLink(a)) {
        return;
      }
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
        return;
      }
      e.preventDefault();

      el.classList.add('app-page-exit');
      window.requestAnimationFrame(function() {
        el.classList.add('app-page-exit-active');
        window.setTimeout(function() {
          window.location.href = a.href;
        }, 150);
      });
    });
  }

  // Toggle the side navigation
  $("#sidebarToggle, #sidebarToggleTop").on('click', function(e) {
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled");
    if ($(".sidebar").hasClass("toggled")) {
      $('.sidebar .collapse').collapse('hide');
    };
  });

  // Close any open menu accordions when window is resized below 768px
  $(window).resize(function() {
    if ($(window).width() < 768) {
      $('.sidebar .collapse').collapse('hide');
    };
  });

  // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
  $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function(e) {
    if ($(window).width() > 768) {
      var e0 = e.originalEvent,
        delta = e0.wheelDelta || -e0.detail;
      this.scrollTop += (delta < 0 ? 1 : -1) * 30;
      e.preventDefault();
    }
  });

  // Scroll to top button appear
  $(document).on('scroll', function() {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
      $('.scroll-to-top').fadeIn();
    } else {
      $('.scroll-to-top').fadeOut();
    }
  });

  // Smooth scrolling using jQuery easing
  $(document).on('click', 'a.scroll-to-top', function(e) {
    var $anchor = $(this);
    $('html, body').stop().animate({
      scrollTop: ($($anchor.attr('href')).offset().top)
    }, 1000, 'easeInOutExpo');
    e.preventDefault();
  });

  $(document).ready(function() {
    ensureSidebarBackdrop();
    animatePageIn();
    installPageTransitions();
    moveToasts();
  });

})(jQuery); // End of use strict

// Modal Javascript

$(document).ready(function () {
  $("#myBtn").click(function () {
    $('.modal').modal('show');
  });

  $("#modalLong").click(function () {
    $('.modal').modal('show');
  });

  $("#modalScroll").click(function () {
    $('.modal').modal('show');
  });

  $('#modalCenter').click(function () {
    $('.modal').modal('show');
  });
});

// Popover Javascript

$(function () {
  $('[data-toggle="popover"]').popover()
});
$('.popover-dismiss').popover({
  trigger: 'focus'
});


// Version in Sidebar

var version = document.getElementById('version-ruangadmin');

version.innerHTML = "Version 1.0.1";