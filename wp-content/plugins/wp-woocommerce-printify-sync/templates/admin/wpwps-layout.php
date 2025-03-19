<div class="wrap wpwps-admin">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-tshirt"></i> Printify Sync</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <?php 
                    $current_page = $_GET['page'] ?? 'wpwps-dashboard';
                    $menu_items = [
                        'wpwps-dashboard' => ['<i class="fas fa-home"></i> Dashboard', 'Dashboard'],
                        'wpwps-settings' => ['<i class="fas fa-cogs"></i> Settings', 'Settings'],
                        'wpwps-products' => ['<i class="fas fa-box"></i> Products', 'Products'],
                        'wpwps-orders' => ['<i class="fas fa-shopping-cart"></i> Orders', 'Orders']
                    ];
                    
                    foreach ($menu_items as $slug => $item) {
                        $active_class = ($current_page === $slug) ? 'active' : '';
                        echo '<li class="nav-item">';
                        echo '<a class="nav-link ' . $active_class . '" href="admin.php?page=' . $slug . '" 
                                aria-current="' . ($active_class ? 'page' : 'false') . '">';
                        echo $item[0];
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <?php 
            if (isset($partials) && is_array($partials)) {
                foreach ($partials as $partial) {
                    echo $this->render("admin/partials/{$partial}.php", $data);
                }
            }
            echo $content; 
        ?>
    </div>
</div>
