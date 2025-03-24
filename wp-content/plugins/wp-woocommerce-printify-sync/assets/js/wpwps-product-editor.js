(function($) {
    'use strict';

    $(document).ready(function() {
        $('.wpwps-ai-suggest').on('click', function() {
            const $button = $(this);
            const $preview = $button.siblings('.wpwps-ai-preview');
            const target = $button.data('target');
            
            $button.prop('disabled', true);
            $preview.html('<span class="spinner is-active"></span>');

            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_get_ai_suggestions',
                    nonce: $('#wpwps_ai_suggestions_nonce').val(),
                    product_id: $('#post_ID').val(),
                    target: target
                },
                success: function(response) {
                    if (response.success) {
                        renderSuggestion($preview, target, response.data.suggestion);
                    } else {
                        $preview.html(`<div class="notice notice-error">${response.data.message}</div>`);
                    }
                },
                error: function() {
                    $preview.html(`<div class="notice notice-error">${wpwps.i18n.ajax_error}</div>`);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        function renderSuggestion($preview, target, suggestion) {
            let html = '<div class="wpwps-ai-suggestion">';
            
            switch (target) {
                case 'title':
                    html += `<p>${suggestion}</p>`;
                    html += `<button type="button" class="button apply-suggestion" data-field="title">${wpwps.i18n.apply_title}</button>`;
                    break;
                case 'description':
                    html += `<div class="suggestion-content">${suggestion}</div>`;
                    html += `<button type="button" class="button apply-suggestion" data-field="description">${wpwps.i18n.apply_description}</button>`;
                    break;
                case 'tags':
                    html += '<ul class="suggestion-tags">';
                    suggestion.forEach(tag => {
                        html += `<li>${tag}</li>`;
                    });
                    html += '</ul>';
                    html += `<button type="button" class="button apply-suggestion" data-field="tags">${wpwps.i18n.apply_tags}</button>`;
                    break;
                case 'yoast_seo':
                    if (suggestion.error) {
                        html += `<div class="notice notice-error">${suggestion.error}</div>`;
                    } else {
                        html += `<div class="yoast-suggestions">`;
                        html += `<p><strong>${wpwps.i18n.seo_title}:</strong> ${suggestion.seo_title}</p>`;
                        html += `<p><strong>${wpwps.i18n.meta_desc}:</strong> ${suggestion.meta_description}</p>`;
                        html += `<p><strong>${wpwps.i18n.focus_keyphrase}:</strong> ${suggestion.focus_keyphrase}</p>`;
                        html += `<button type="button" class="button apply-suggestion" data-field="yoast_seo">${wpwps.i18n.apply_seo}</button>`;
                        html += '</div>';
                    }
                    break;
            }

            html += '</div>';
            $preview.html(html);

            // Initialize apply buttons
            $('.apply-suggestion').on('click', function() {
                applySuggestion($(this).data('field'), suggestion);
            });
        }

        function applySuggestion(field, content) {
            switch (field) {
                case 'title':
                    $('#title').val(content).trigger('change');
                    break;
                case 'description':
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                        tinyMCE.get('content').setContent(content);
                    }
                    break;
                case 'tags':
                    $('#new-tag-product_tag').val(content.join(', '));
                    $('.tagadd').click();
                    break;
                case 'yoast_seo':
                    if (typeof YoastSEO !== 'undefined') {
                        YoastSEO.app.changeInputValue('title', content.seo_title);
                        YoastSEO.app.changeInputValue('metadesc', content.meta_description);
                        YoastSEO.app.changeInputValue('focuskw', content.focus_keyphrase);
                    }
                    break;
            }
        }
    });
})(jQuery);
