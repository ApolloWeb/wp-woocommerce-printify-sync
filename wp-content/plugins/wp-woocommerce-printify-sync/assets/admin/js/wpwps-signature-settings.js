(function($) {
    'use strict';

    const SignatureSettings = {
        init: function() {
            this.initLogoUploader();
            this.initSocialLinks();
            this.initFormHandler();
            this.initLivePreview();
        },

        initLogoUploader: function() {
            $('#upload-signature-logo').on('click', function(e) {
                e.preventDefault();
                
                const uploader = wp.media({
                    title: 'Select Signature Logo',
                    multiple: false
                }).on('select', function() {
                    const attachment = uploader.state().get('selection').first().toJSON();
                    $('.wpwps-logo-preview').attr('src', attachment.url).show();
                    $('#signature-logo-url').val(attachment.url);
                    SignatureSettings.updatePreview();
                }).open();
            });
        },

        initSocialLinks: function() {
            $('#add-social-link').on('click', function() {
                const networks = ['facebook', 'twitter', 'linkedin', 'instagram'];
                const $container = $('#social-links-container');
                
                const template = `
                    <div class="input-group mb-2">
                        <select class="form-select" style="max-width: 120px;">
                            ${networks.map(n => `<option value="${n}">${n.charAt(0).toUpperCase() + n.slice(1)}</option>`).join('')}
                        </select>
                        <input type="url" class="form-control" placeholder="URL">
                        <button type="button" class="btn btn-outline-danger remove-social-link">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                $(template).insertBefore(this);
            });
            
            $(document).on('click', '.remove-social-link', function() {
                $(this).closest('.input-group').remove();
                SignatureSettings.updatePreview();
            });
        },

        updatePreview: function() {
            const data = {
                action: 'wpwps_get_signature_preview',
                logo: $('#signature-logo-url').val(),
                social_links: this.getSocialLinks()
            };

            WPWPS.api.post('get_signature_preview', data)
                .then(response => {
                    if (response.success) {
                        $('.wpwps-signature-preview').html(response.data.html);
                    }
                });
        },

        getSocialLinks: function() {
            const links = {};
            $('#social-links-container .input-group').each(function() {
                const network = $(this).find('select').val();
                const url = $(this).find('input[type="url"]').val();
                if (url) links[network] = url;
            });
            return links;
        }
    };

    $(document).ready(() => SignatureSettings.init());

})(jQuery);
