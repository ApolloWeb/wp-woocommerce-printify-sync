<?php
/**
 * @var \League\Plates\Template\Template $this
 * @var string $title
 * @var string $content
 */
?>
<div class="wrap wpps-admin">
    <nav class="wpps-navbar navbar sticky-top mb-4">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="wpps-sidebar-toggle btn btn-link me-3">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="wp-heading-inline m-0"><?= esc_html($title) ?></h1>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown me-3">
                    <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            2
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Notification 1</a></li>
                        <li><a class="dropdown-item" href="#">Notification 2</a></li>
                    </ul>
                </div>
                <img src="<?= get_avatar_url(get_current_user_id(), ['size' => 32]) ?>" 
                     class="rounded-circle" 
                     alt="User avatar">
            </div>
        </div>
    </nav>

    <div class="wpps-container container-fluid">
        <div class="row">
            <div class="wpps-sidebar col-md-2">
                <?= $this->insert('admin/partials/sidebar') ?>
            </div>
            <div class="wpps-main-content col-md-10">
                <?= $content ?>
            </div>
        </div>
    </div>
</div>
