                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page,
                        'add_args' => array(
                            'level' => $level,
                            'search' => $search,
                            'date_from' => $date_from,
                            'date_to' => $date_to
                        )
                    ));
                    ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#clear-logs').on('click', function() {
            if (confirm('<?php _e('Are you sure you want to clear these logs?', 'wp-woocommerce-printify-sync'); ?>')) {
                var nonce = $(this).data('nonce');
                var level = $('#level').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpwprintifysync_clear_logs',
                        nonce: nonce,
                        level: level,
                        date_before: ''
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Server error. Please try again.');
                    }
                });
            }
        });
    });
</script>