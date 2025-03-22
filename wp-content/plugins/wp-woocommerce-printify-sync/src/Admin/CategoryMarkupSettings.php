<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class CategoryMarkupSettings
{
    public function init()
    {
        add_action('product_cat_add_form_fields', [$this, 'addMarkupField']);
        add_action('product_cat_edit_form_fields', [$this, 'editMarkupField']);
        add_action('created_product_cat', [$this, 'saveMarkup']);
        add_action('edited_product_cat', [$this, 'saveMarkup']);
    }

    public function addMarkupField()
    {
        ?>
        <div class="form-field">
            <label for="wpwps_category_markup"><?php _e('Markup Percentage', 'wp-woocommerce-printify-sync'); ?></label>
            <input type="number" name="wpwps_category_markup" id="wpwps_category_markup" step="0.01" min="0" />
            <p class="description"><?php _e('Enter markup percentage for this category (e.g. 100 for 100% markup)', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
        <?php
    }

    public function editMarkupField($term)
    {
        $markup = get_term_meta($term->term_id, '_wpwps_category_markup', true);
        ?>
        <tr class="form-field">
            <th><label for="wpwps_category_markup"><?php _e('Markup Percentage', 'wp-woocommerce-printify-sync'); ?></label></th>
            <td>
                <input type="number" name="wpwps_category_markup" id="wpwps_category_markup" step="0.01" min="0" value="<?php echo esc_attr($markup); ?>" />
                <p class="description"><?php _e('Enter markup percentage for this category (e.g. 100 for 100% markup)', 'wp-woocommerce-printify-sync'); ?></p>
            </td>
        </tr>
        <?php
    }

    public function saveMarkup($term_id)
    {
        if (isset($_POST['wpwps_category_markup'])) {
            update_term_meta(
                $term_id,
                '_wpwps_category_markup',
                floatval($_POST['wpwps_category_markup'])
            );
        }
    }
}
