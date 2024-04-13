{{-- {{ getSettings()->menu_text_color }}
{{ getSettings()->secondary_color }}
{{ getSettings()->active_hover_color }} --}}

<style>
    /* Main Menu Base */
    /* -------------- */
    .menu-title{
      color: {{ getSettings()->menu_text_color }} !important;
    }

    body.semi-dark-layout .main-menu-content .navigation-main .nav-item i {
      color:  {{ getSettings()->menu_text_color }} !important;
    }

    body.vertical-layout.vertical-menu-modern.menu-expanded .main-menu .navigation li.has-sub > a:not(.mm-next)::after{
      color:  {{ getSettings()->menu_text_color }} !important;
    }

    svg a {
      color: {{ getSettings()->menu_text_color }} !important;
    }

    .customer-details{
      color: {{ getSettings()->dasboard_customer_details_color }} !important;
    }

    .block-header-color{
      color: {{ getSettings()->block_header_color }} !important;
    }

    .semi-dark-layout .main-menu {
      background-color: {{ getSettings()->main_menu_background }} !important;
    }

    /* Scroll bacr */
    .main-menu .ps__thumb-y {
      background-color:  {{ getSettings()->active_color }} !important;
    }

    .customer-details .main-menu {
      background-color: {{ getSettings()->customer_details }} !important;
    }

    .main-menu.menu-dark .navigation > li.active:not(.sidebar-group-active) > a {
      background: {{ getSettings()->active_color  }};
    }

    .nav-item.nav-toggle i {
      color: {{ getSettings()->active_color  }};
    }


    .main-menu {
      z-index: 1031;
      position: absolute;
      display: table-cell;
      height: 100%;
      overflow: hidden;
    }

    .main-menu .ps__thumb-y {
      background-color: #d1d7de;
    }

    .main-menu.menu-light {
      color: #727E8C;
      background: #F2F4F4;
      border: #DFE3E7;
    }

    .main-menu.menu-light .navigation {
      background: #F2F4F4;
    }

    .main-menu.menu-light .navigation .navigation-header {
      color: #bac0c7;
      margin: calc(2.2rem - 0.5rem) 0 0.5rem 1.8rem;
      padding: 0;
      letter-spacing: 1px;
    }

    .main-menu.menu-light .navigation li.has-sub ul {
      padding: 7px 0 0;
      margin: -7px 0 0;
    }

    .main-menu.menu-light .navigation li.has-sub ul li.has-sub ul.menu-content > li a {
      padding: 10px 20px !important;
      transition: all 0.35s ease !important;
    }

    .main-menu.menu-light .navigation li.has-sub ul li.has-sub ul.menu-content > li a:hover {
      padding-left: 25px !important;
    }

    .main-menu.menu-light .navigation li a {
      display: flex;
      align-items: center;
      color: #8494a7;
      padding: 10px 12px;
    }

    .main-menu.menu-light .navigation > li {
      margin: 0 1rem;
      transition: background-color 0.5s ease;
    }

    .main-menu.menu-light .navigation > li.nav-item:not(.has-sub) a {
      padding: 10px 12px;
    }

    .main-menu.menu-light .navigation > li.open.sidebar-group-active > a {
      padding: 10px 15px;
    }

    .main-menu.menu-light .navigation > li.nav-item.open > a, .main-menu.menu-light .navigation > li.nav-item.sidebar-group-active > a {
      margin: 0 11px 0;
      padding: 9px 0;
      transition: transform 0.25s ease 0s, -webkit-transform 0.25s ease 0s;
    }

    .main-menu.menu-light .navigation > li.nav-item.open > a i, .main-menu.menu-light .navigation > li.nav-item.sidebar-group-active > a i {
      color: #5A8DEE !important;
    }

    .main-menu.menu-light .navigation > li.nav-item.open.has-sub.open, .main-menu.menu-light .navigation > li.nav-item.open.has-sub.sidebar-group-active, .main-menu.menu-light .navigation > li.nav-item.sidebar-group-active.has-sub.open, .main-menu.menu-light .navigation > li.nav-item.sidebar-group-active.has-sub.sidebar-group-active {
      border-radius: 0.267rem;
      border: 1px solid #DFE3E7;
      background-color: #fafbfb;
      transition: none;
    }

    .main-menu.menu-light .navigation > li.nav-item.open.has-sub > a:not(.mm-next):after, .main-menu.menu-light .navigation > li.nav-item.sidebar-group-active.has-sub > a:not(.mm-next):after {
      right: 7px !important;
    }

    .main-menu.menu-light .navigation > li.nav-item.open .menu-content li a, .main-menu.menu-light .navigation > li.nav-item.sidebar-group-active .menu-content li a {
      padding: 10px 18px;
    }

    .main-menu.menu-light .navigation > li.nav-item.open .menu-content li > a:hover, .main-menu.menu-light .navigation > li.nav-item.sidebar-group-active .menu-content li > a:hover {
      padding-left: 15px !important;
    }

    .main-menu.menu-light .navigation > li:not(.open) > ul {
      display: none;
    }

    .main-menu.menu-light .navigation > li.active:not(.sidebar-group-active) > a {
      background: rgba(90, 141, 238, 0.15);
      color: #5A8DEE;
      border-radius: 0.267rem;
    }

    .main-menu.menu-light .navigation > li .active > a {
      margin-bottom: 0;
    }

    .main-menu.menu-light .navigation > li .active .hover {
      background: #e7ebeb;
    }

    .main-menu.menu-light .navigation > li ul li > a {
      padding: 10px 9px !important;
      margin: 0 11px;
    }

    .main-menu.menu-light .navigation > li ul .has-sub:not(.open) > ul {
      display: none;
    }

    .main-menu.menu-light .navigation > li ul .open > a,
    .main-menu.menu-light .navigation > li ul .sidebar-group-active > a {
      color: #727E8C;
    }

    .main-menu.menu-light .navigation > li ul .open > ul,
    .main-menu.menu-light .navigation > li ul .sidebar-group-active > ul {
      display: block;
    }

    .main-menu.menu-light .navigation > li ul .open > ul .open > ul,
    .main-menu.menu-light .navigation > li ul .sidebar-group-active > ul .open > ul {
      display: block;
    }

    .main-menu.menu-light .navigation > li ul .open.active,
    .main-menu.menu-light .navigation > li ul .sidebar-group-active.active {
      background-color: inherit;
    }

    .main-menu.menu-light .navigation > li ul .active {
      background: rgba(90, 141, 238, 0.15);
    }

    .main-menu.menu-light .navigation > li ul .active > a {
      color: #5A8DEE;
    }

    .main-menu.menu-light .navigation > li > ul > li:first-child > a {
      border-top: 1px solid #DFE3E7;
    }

    .main-menu.menu-light .navigation > li > ul > li.active:first-child > a {
      border-top: none;
    }

    .main-menu.menu-dark {
      color: #8a99b5;
      background: #1a233a;
      border: #464d5c;
    }

    .main-menu.menu-dark .navigation {
      background: #1a233a;
    }

    .main-menu.menu-dark .navigation .navigation-header {
      color: #bac0c7;
      margin: calc(2.2rem - 0.5rem) 0 0.5rem 1.8rem;
      padding: 0;
      letter-spacing: 1px;
    }

    .main-menu.menu-dark .navigation li.has-sub ul {
      padding: 7px 0 0;
      margin: -7px 0 0;
    }

    .main-menu.menu-dark .navigation li.has-sub ul li.has-sub ul.menu-content > li a {
      padding: 10px 20px !important;
      transition: all 0.35s ease !important;
    }

    .main-menu.menu-dark .navigation li.has-sub ul li.has-sub ul.menu-content > li a:hover {
      padding-left: 25px !important;
    }

    .main-menu.menu-dark .navigation li a {
      display: flex;
      align-items: center;
      padding: 10px 12px;
      color: {{ getSettings()->menu_text_color }} !important;
    }

    .main-menu.menu-dark .navigation > li {
      margin: 0 1rem;
      transition: background-color 0.5s ease;
    }

    .main-menu.menu-dark .navigation > li.nav-item:not(.has-sub) a {
      padding: 10px 12px;
    }

    .main-menu.menu-dark .navigation > li.open.sidebar-group-active > a {
      padding: 10px 15px;
    }

    .main-menu.menu-dark .navigation > li.nav-item.open > a, .main-menu.menu-dark .navigation > li.nav-item.sidebar-group-active > a {
      margin: 0 11px 0;
      padding: 9px 0;
      transition: transform 0.25s ease 0s, -webkit-transform 0.25s ease 0s;
    }

    .main-menu.menu-dark .navigation > li.nav-item.open > a i, .main-menu.menu-dark .navigation > li.nav-item.sidebar-group-active > a i {
      color: #5A8DEE !important;
    }

    .main-menu.menu-dark .navigation > li.nav-item.open.has-sub.open, .main-menu.menu-dark .navigation > li.nav-item.open.has-sub.sidebar-group-active, .main-menu.menu-dark .navigation > li.nav-item.sidebar-group-active.has-sub.open, .main-menu.menu-dark .navigation > li.nav-item.sidebar-group-active.has-sub.sidebar-group-active {
      border-radius: 0.267rem;
      border: 1px solid #464d5c;
      background-color: #1a233a;
      transition: none;
    }

    .main-menu.menu-dark .navigation > li.nav-item.open.has-sub > a:not(.mm-next):after, .main-menu.menu-dark .navigation > li.nav-item.sidebar-group-active.has-sub > a:not(.mm-next):after {
      right: 7px !important;
    }

    .main-menu.menu-dark .navigation > li.nav-item.open .menu-content li a, .main-menu.menu-dark .navigation > li.nav-item.sidebar-group-active .menu-content li a {
      padding: 10px 18px;
    }

    .main-menu.menu-dark .navigation > li.nav-item.open .menu-content li > a:hover, .main-menu.menu-dark .navigation > li.nav-item.sidebar-group-active .menu-content li > a:hover {
      padding-left: 15px !important;
    }

    .main-menu.menu-dark .navigation > li:not(.open) > ul {
      display: none;
    }

   

    .main-menu.menu-dark .navigation > li .active > a {
      margin-bottom: 0;
    }

    .main-menu.menu-dark .navigation > li .active .hover {
      background: #141b2c;
    }

    .main-menu.menu-dark .navigation > li ul li > a {
      padding: 10px 9px !important;
      margin: 0 11px;
    }

    .main-menu.menu-dark .navigation > li ul .has-sub:not(.open) > ul {
      display: none;
    }

    .main-menu.menu-dark .navigation > li ul .open > a,
    .main-menu.menu-dark .navigation > li ul .sidebar-group-active > a {
      color: #8a99b5;
    }

    .main-menu.menu-dark .navigation > li ul .open > ul,
    .main-menu.menu-dark .navigation > li ul .sidebar-group-active > ul {
      display: block;
    }

    .main-menu.menu-dark .navigation > li ul .open > ul .open > ul,
    .main-menu.menu-dark .navigation > li ul .sidebar-group-active > ul .open > ul {
      display: block;
    }

    .main-menu.menu-dark .navigation > li ul .open.active,
    .main-menu.menu-dark .navigation > li ul .sidebar-group-active.active {
      background-color: inherit;
    }

    .main-menu.menu-dark .navigation > li ul .active {
      background: rgba(90, 141, 238, 0.15);
    }

    .main-menu.menu-dark .navigation > li ul .active > a {
      color: #5A8DEE;
    }

    .main-menu.menu-dark .navigation > li > ul > li:first-child > a {
      border-top: 1px solid #464d5c;
    }

    .main-menu.menu-dark .navigation > li > ul > li.active:first-child > a {
      border-top: none;
    }

    .main-menu.menu-fixed {
      position: fixed;
      top: 0;
    }

    .main-menu.menu-static {
      height: auto;
      top: 0;
      padding-bottom: calc(100% - 40rem);
    }

    .main-menu.menu-static .main-menu-content {
      height: unset !important;
    }

    .main-menu .shadow-bottom {
      /* Menu Scroll Shadow */
      display: none;
      position: absolute;
      z-index: 2;
      height: 60px;
      width: 100%;
      pointer-events: none;
      margin-top: -1.3rem;
      filter: blur(5px);
      background: linear-gradient(#f2f4f4 41%, rgba(255, 255, 255, 0.11) 95%, rgba(255, 255, 255, 0));
    }

    .main-menu .navbar-header {
      height: 100%;
      width: 260px;
      height: 4.6rem;
      position: relative;
      padding: 0.35rem 1.45rem 0.3rem 1.3rem;
      transition: 300ms ease all;
      cursor: pointer;
      z-index: 3;
    }

    .main-menu .navbar-header .navbar-brand {
      margin-top: 0.75rem;
      display: flex;
      align-items: center;
    }

    .main-menu .navbar-header .navbar-brand .brand-logo {
      height: 27px;
      width: 35px;
      float: left;
      margin-top: 0.2rem;
      margin-left: 3px;
    }

    .main-menu .navbar-header .navbar-brand .brand-logo .logo {
      height: 26px;
      display: flex;
      position: relative;
      left: 6px;
    }

    .main-menu .navbar-header .navbar-brand .brand-text {
      color: #5A8DEE;
      padding-left: 0.7rem;
      font-weight: 500;
      letter-spacing: 0.04rem;
      font-size: 1.5rem;
      float: left;
      animation: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) 0s normal forwards 1 fadein;
    }

    .main-menu .navbar-header .modern-nav-toggle {
      animation: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) 0s normal forwards 1 fadein;
      margin: 0.75rem 0 0;
    }

    .main-menu .main-menu-content {
      height: calc(100% - 6rem) !important;
      position: relative;
    }

    .main-menu ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .main-menu ul.navigation-main {
      overflow-x: hidden;
    }
</style>