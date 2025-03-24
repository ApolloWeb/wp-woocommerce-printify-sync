<?php
$current_user = wp_get_current_user();
$avatar = get_avatar_url($current_user->ID, ['size' => 32]);
$role_names = [];
foreach ($current_user->roles as $role) {
    $role_object = get_role($role);
    $role_names[] = ucfirst($role);
}
$role_display = implode(', ', $role_names);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <i class="fas fa-tshirt text-primary me-2"></i>
      <span class="fw-bold"><?php _e('Printify Sync','wp-woocommerce-printify-sync'); ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#wpwppsNavbar" aria-controls="wpwppsNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="wpwppsNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link px-3" href="<?php echo admin_url('admin.php?page=wp-woocommerce-printify-sync'); ?>">
            <i class="fas fa-chart-line me-1"></i> <?php _e('Dashboard','wp-woocommerce-printify-sync'); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3" href="<?php echo admin_url('admin.php?page=wpwpps-settings'); ?>">
            <i class="fas fa-cog me-1"></i> <?php _e('Settings','wp-woocommerce-printify-sync'); ?>
          </a>
        </li>
      </ul>
      
      <div class="d-flex align-items-center">
        <div class="dropdown">
          <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="rounded-circle border me-2" width="32" height="32">
            <div class="d-none d-lg-block text-start">
              <div class="fw-semibold"><?php echo esc_html($current_user->display_name); ?></div>
              <div class="text-muted small"><?php echo esc_html($role_display); ?></div>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="<?php echo esc_url(get_edit_profile_url()); ?>">
              <i class="fas fa-user me-2 text-secondary"></i> <?php _e('My Profile','wp-woocommerce-printify-sync'); ?>
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(wp_logout_url()); ?>">
              <i class="fas fa-sign-out-alt me-2 text-secondary"></i> <?php _e('Logout','wp-woocommerce-printify-sync'); ?>
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>
