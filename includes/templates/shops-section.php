<div class="wrap wwps-shops">
    <h2><?php esc_html_e( 'Printify Shops', 'wwps' ); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Shop Name', 'wwps' ); ?></th>
                <th><?php esc_html_e( 'Shop ID', 'wwps' ); ?></th>
                <th><?php esc_html_e( 'Action', 'wwps' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3"><?php esc_html_e( 'Loading shops...', 'wwps' ); ?></td>
            </tr>
        </tbody>
    </table>
    <button class="button button-primary" id="manual-import"><?php esc_html_e( 'Import Products', 'wwps' ); ?></button>
    <div id="import-progress" style="display: none;">
        <h2><?php esc_html_e( 'Import Progress', 'wwps' ); ?></h2>
        <div id="progress-bar" style="width: 100%; background-color: #e0e0e0;">
            <div id="progress-bar-fill" style="width: 0%; height: 30px; background-color: #4caf50;"></div>
        </div>
    </div>
</div>