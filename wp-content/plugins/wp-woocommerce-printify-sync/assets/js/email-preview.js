jQuery(function($) {
    const preview = {
        init: function() {
            this.container = $('.wpwps-email-preview-container');
            this.frame = $('#wpwps-preview-frame');
            this.typeSelect = $('#wpwps-email-type');
            this.previewButton = $('#wpwps-preview-button');
            this.testButton = $('#wpwps-send-test');

            this.bindEvents();
            this.loadPreview();
        },

        bindEvents: function() {
            this.previewButton.on('click', () => this.loadPreview());
            this.testButton.on('click', () => this.sendTestEmail());
            this.typeSelect.on('change', () => this.loadPreview());
        },

        loadPreview: function() {
            const overlay = this.showLoading();

            $.ajax({
                url: wpwpsEmailPreview.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_preview_email',
                    email_type: this.typeSelect.val(),
                    _ajax_nonce: wpwpsEmailPreview.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updatePreview(response.data.preview);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError(wpwpsEmailPreview.i18n.error);
                },
                complete: () => {
                    overlay.remove();
                }
            });
        },

        sendTestEmail: function() {
            const email = prompt(wpwpsEmailPreview.i18n.enterEmail);
            if (!email) {
                return;
            }

            const overlay = this.showLoading();

            $.ajax({
                url: wpwpsEmailPreview.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_send_test_email',
                    email_type: this.typeSelect.val(),
                    test_email: email,
                    _ajax_nonce: wpwpsEmailPreview.nonce
                },
                success: (response) => {
                    if (response.success) {
                        alert(wpwpsEmailPreview.i18n.emailSent);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError(wpwpsEmailPreview.i18n.error);
                },
                complete: () => {
                    overlay.remove();
                }
            });
        },

        updatePreview: function(content) {
            const frame = this.frame[0];
            const doc = frame.contentDocument || frame.contentWindow.document;
            
            doc.open();
            doc.write(content);
            doc.close();

            // Adjust iframe height to content
            frame.style.height = doc.body.scrollHeight + 'px';
        },

        showLoading: function() {
            const overlay = $('<div class="wpwps-loading-overlay"></div>');
            const spinner = $('<div class="wpwps-loading-spinner"></div>');
            
            overlay.append(spinner);
            this.container.append(overlay);
            
            return overlay;
        },

        showError: function(message) {
            const notice = $('<div class="notice notice-error"></div>')
                .text(message)
                .insertBefore(this.container);

            setTimeout(() => {
                notice.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    preview.init();
});