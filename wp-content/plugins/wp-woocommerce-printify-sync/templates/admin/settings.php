<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrapper">
    <h1>Printify Sync Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('wpwps_settings'); ?>

        <div class="wpwps-card">
            <div class="wpwps-card-header">
                <h2>Storage Settings</h2>
            </div>
            <div class="wpwps-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable R2 Offload</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="wpwps_enable_r2_offload" 
                                       value="1" 
                                       <?php checked(get_option('wpwps_enable_r2_offload')); ?>>
                                Enable Cloudflare R2 integration
                            </label>
                            <p class="description">
                                Requires a compatible R2 offload plugin to be installed and configured.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>