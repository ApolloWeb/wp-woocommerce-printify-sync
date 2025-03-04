<div class="wrap">
    <h1>Select Default Shop</h1>
    <form method="post" action="">
        <?php wp_nonce_field('shop_select', 'shop_select_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Default Shop</th>
                <td>
                    <select name="default_shop" required>
                        <?php foreach ($shops as $shop) : ?>
                            <option value="<?php echo esc_attr($shop['id']); ?>" <?php selected(get_option('default_shop'), $shop['id']); ?>>
                                <?php echo esc_html($shop['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Save Changes'); ?> Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added:         <?php submit_button('Save Changes'); ?> Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
