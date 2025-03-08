<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Admin;

use ApolloWeb\WpWooCommercePrintifySync\Helpers\Enqueue;

class AdminFunctions
{
    public static function addAdminMenu()
    {
        add_menu_page(
            'Printify Sync',
            'Printify Sync',
            'manage_options',
            'wpwpsp_dashboard',
            [self::class, 'renderDashboardPage'],
            'dashicons-tshirt',
            6
        );

        add_submenu_page(
            'wpwpsp_dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'wpwpsp_settings',
            [self::class, 'renderSettingsPage']
        );
    }

    public static function renderDashboardPage()
    {
        ?>
        <div class="wrap">
            <div class="content-wrapper" style="min-height: 1604.44px;">
                <section class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1>Dashboard</h1>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <!-- Sales Chart -->
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Sales Chart</h3>
                                        <div class="card-tools">
                                            <select id="sales-chart-filter" class="form-control">
                                                <option value="day">Day</option>
                                                <option value="week">Week</option>
                                                <option value="month">Month</option>
                                                <option value="year">Year</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="salesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Widgets -->
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3>150</h3>
                                        <p>New Orders</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-shopping-cart"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3>53%</h3>
                                        <p>Bounce Rate</p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h3>44</h3>
                                        <p>User Registrations</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-user-plus"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3>65</h3>
                                        <p>Unique Visitors</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-chart-pie"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-primary">
                                    <div class="inner">
                                        <h3>85</h3>
                                        <p>Products Sold</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-box"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-secondary">
                                    <div class="inner">
                                        <h3>120</h3>
                                        <p>Support Tickets</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-life-ring"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-light">
                                    <div class="inner">
                                        <h3>95</h3>
                                        <p>Reviews</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-star"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-dark">
                                    <div class="inner">
                                        <h3>110</h3>
                                        <p>New Customers</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-users"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public static function renderSettingsPage()
    {
        ?>
        <div class="wrap">
            <div class="content-wrapper" style="min-height: 1604.44px;">
                <section class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1>Settings</h1>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="content">
                    <div class="container-fluid">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('wpwpsp_settings');
                            do_settings_sections('wpwpsp_settings');
                            ?>
                            <div class="form-group">
                                <label for="printify_api_url">Printify API URL</label>
                                <input type="text" name="printify_api_url" id="printify_api_url" class="form-control" value="<?php echo esc_attr(get_option('printify_api_url')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="printify_api_key">Printify API Key</label>
                                <input type="text" name="printify_api_key" id="printify_api_key" class="form-control" value="<?php echo esc_attr(get_option('printify_api_key')); ?>">
                            </div>
                            <?php submit_button(); ?>
                        </form>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }
}

add_action('admin_enqueue_scripts', ['ApolloWeb\WpWooCommercePrintifySync\Helpers\Enqueue', 'adminAssets']);
add_action('admin_menu', ['ApolloWeb\WpWooCommercePrintifySync\Admin\AdminFunctions', 'addAdminMenu']);