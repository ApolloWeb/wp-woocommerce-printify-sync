(function($) {
    'use strict';

    const EmailSettings = {
        init: function() {
            this.initializeFormHandlers();
            this.initializeTestButtons();
            this.initializePasswordToggles();
            this.startStatusMonitor();
        },

        initializeFormHandlers: function() {
            $('#save-email-settings').on('click', (e) => {
                e.preventDefault();
                this.saveSettings();
            });

            // Auto-update port based on security selection
            $('#pop3-security, #smtp-security').on('change', function() {
                const type = $(this).attr('id').split('-')[0];
                const port = $(this).val() === 'ssl' ? 
                    (type === 'pop3' ? '995' : '465') : 
                    (type === 'pop3' ? '110' : '587');
                $(`#${type}-port`).val(port);
            });
        },

        initializeTestButtons: function() {
            $('#test-pop3').on('click', () => this.testConnection('pop3'));
            $('#test-smtp').on('click', () => this.testConnection('smtp'));
        },

        testConnection: function(type) {
            const $button = $(`#test-${type}`);
            const originalText = $button.html();
            
            $button.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Testing...');

            const data = {
                type: type,
                host: $(`#${type}-host`).val(),
                port: $(`#${type}-port`).val(),
                security: $(`#${type}-security`).val(),
                username: $(`#${type}-username`).val(),
                password: $(`#${type}-password`).val()
            };

            WPWPS.api.post('test_email_connection', data)
                .then(response => {
                    if (response.success) {
                        WPWPS.toast.success(`${type.toUpperCase()} connection successful`);
                        $(`#${type}-status`).removeClass('bg-warning bg-danger').addClass('bg-success').text('Connected');
                    } else {
                        WPWPS.toast.error(response.data.message);
                        $(`#${type}-status`).removeClass('bg-success bg-warning').addClass('bg-danger').text('Failed');
                    }
                })
                .finally(() => {
                    $button.prop('disabled', false).html(originalText);
                });
        },

        saveSettings: function() {
            const data = {
                pop3: {
                    host: $('#pop3-host').val(),
                    port: $('#pop3-port').val(),
                    security: $('#pop3-security').val(),
                    username: $('#pop3-username').val(),
                    password: $('#pop3-password').val(),
                    delete_messages: $('#pop3-delete').prop('checked')
                },
                smtp: {
                    host: $('#smtp-host').val(),
                    port: $('#smtp-port').val(),
                    security: $('#smtp-security').val(),
                    username: $('#smtp-username').val(),
                    password: $('#smtp-password').val()
                },
                queue: {
                    check_interval: $('#email-check-interval').val(),
                    process_interval: $('#queue-process-interval').val(),
                    batch_size: $('#queue-batch-size').val(),
                    retry_limit: $('#queue-retry-limit').val()
                }
            };

            WPWPS.api.post('save_email_settings', data)
                .then(response => {
                    if (response.success) {
                        WPWPS.toast.success('Email settings saved successfully');
                    }
                });
        },

        startStatusMonitor: function() {
            // Update status every minute
            setInterval(() => this.updateStatus(), 60000);
            this.updateStatus();
        },

        updateStatus: function() {
            WPWPS.api.get('email_system_status')
                .then(response => {
                    if (response.success) {
                        this.updateStatusDisplay(response.data);
                    }
                });
        },

        updateStatusDisplay: function(data) {
            // Update connection status badges
            ['pop3', 'smtp'].forEach(type => {
                $(`#${type}-status`)
                    .removeClass('bg-success bg-warning bg-danger')
                    .addClass(this.getStatusClass(data[type].status))
                    .text(data[type].status);
            });

            // Update queue progress
            const total = data.queue.total || 1;
            $('.progress-bar.bg-success').css('width', `${(data.queue.processing / total * 100)}%`);
            $('.progress-bar.bg-warning').css('width', `${(data.queue.pending / total * 100)}%`);
            $('.progress-bar.bg-danger').css('width', `${(data.queue.failed / total * 100)}%`);

            // Update statistics
            $('#next-process').text(data.queue.next_process);
            $('#process-rate').text(`${data.queue.rate}/min`);
        },

        getStatusClass: function(status) {
            switch(status.toLowerCase()) {
                case 'connected': return 'bg-success';
                case 'connecting': return 'bg-warning';
                default: return 'bg-danger';
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        EmailSettings.init();
    });

})(jQuery);
