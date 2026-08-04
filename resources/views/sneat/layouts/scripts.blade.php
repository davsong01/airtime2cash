<!-- Core JS -->
<script src="{{ asset('modern-assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('modern-assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('modern-assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('modern-assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>
<script src="{{ asset('modern-assets/vendor/libs/pickr/pickr.js') }}"></script>
<script src="{{ asset('modern-assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('modern-assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('modern-assets/vendor/js/menu.js') }}"></script>

<!-- Vendors JS -->
<script src="{{ asset('modern-assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

<script>
  window.config = window.config || {};
  window.config.enableMenuLocalStorage = false;
  window.assetsPath = document.documentElement.getAttribute('data-assets-path') || "{{ rtrim(asset('modern-assets'), '/') }}/";
  window.templateName = document.documentElement.getAttribute('data-template') || "vertical-menu-template-bordered";

  document.addEventListener("DOMContentLoaded", function () {
    var toggle = document.getElementById("nav-semi-dark-toggle");
    var menu = document.getElementById("layout-menu");
    var storageKey = "2cash-semi-dark-menu";

    if (!toggle || !menu) {
      return;
    }

    var applyState = function (enabled) {
      if (enabled) {
        menu.setAttribute("data-bs-theme", "dark");
        document.documentElement.setAttribute("data-semidark-menu", "true");
      } else {
        menu.removeAttribute("data-bs-theme");
        document.documentElement.removeAttribute("data-semidark-menu");
      }
    };

    try {
      applyState(localStorage.getItem(storageKey) === "true");
    } catch (error) {
      applyState(false);
    }

    toggle.addEventListener("click", function () {
      var isEnabled = menu.getAttribute("data-bs-theme") === "dark";
      var nextState = !isEnabled;

      applyState(nextState);

      try {
        localStorage.setItem(storageKey, String(nextState));
      } catch (error) {}
    });

    var scrollTopBtn = document.getElementById("sneat-scroll-top");
    if (scrollTopBtn) {
      var toggleScrollTop = function () {
        if (window.scrollY > 400) {
          $(scrollTopBtn).fadeIn();
        } else {
          $(scrollTopBtn).fadeOut();
        }
      };

      toggleScrollTop();
      window.addEventListener("scroll", toggleScrollTop, { passive: true });

      $(scrollTopBtn).on("click", function () {
        $("html, body").animate({ scrollTop: 0 }, 1000);
      });
    }
  });
</script>

<!-- Main JS -->
<script src="{{ asset('modern-assets/js/main.js') }}"></script>

<!-- Page JS -->
<script src="{{ asset('modern-assets/js/dashboards-analytics.js') }}"></script>

@yield('page-script')
</body>
</html>
