(function($) {
    'use strict';

    const WPWPSSEO = {
        init: function() {
            this.addAIButtons();
            this.initEventListeners();
        },

        addAIButtons: function() {
            $('.wpseo-metabox-menu').append(`
                <li class="wpwps-ai-tab">
                    <a class="wpseo-meta-section-link" href="#wpseo_ai">
                        <span class="dashicons dashicons-image-filter"></span>
                        AI Suggestions
                    </a>
                </li>
            `);

            $('#wpseo-meta-section-content').after(`
                <div id="wpseo_ai" class="wpseo-meta-section wpwps-ai-suggestions">
                    <div class="wpwps-seo-actions">
                        <button type="button" class="button wpwps-analyze-seo">
                            Analyze with AI
                        </button>
                    </div>
                    <div class="wpwps-seo-results"></div>
                </div>
            `);
        },

        initEventListeners: function() {
            $('.wpwps-analyze-seo').on('click', () => this.analyzeSEO());
        },

        analyzeSEO: function() {
            const $btn = $('.wpwps-analyze-seo');
            $btn.prop('disabled', true).text('Analyzing...');

            WPWPS.api.post('analyze_seo', {
                post_id: $('#post_ID').val(),
                type: 'product'
            }).then(response => {
                if (response.success) {
                    this.displaySuggestions(response.data);
                }
            }).finally(() => {
                $btn.prop('disabled', false).text('Analyze with AI');
            });
        },

        displaySuggestions: function(data) {
            const html = `
                <div class="wpwps-seo-suggestion-group">
                    <h3>Meta Title Suggestion</h3>
                    <p>${data.suggestions.suggested_title}</p>
                    <button type="button" class="button apply-suggestion" data-target="title">
                        Apply
                    </button>
                </div>
                <div class="wpwps-seo-suggestion-group">
                    <h3>Meta Description Suggestion</h3>
                    <p>${data.suggestions.suggested_description}</p>
                    <button type="button" class="button apply-suggestion" data-target="description">
                        Apply
                    </button>
                </div>
                <div class="wpwps-seo-suggestion-group">
                    <h3>Suggested Focus Keyphrase</h3>
                    <p>${data.suggestions.focus_keyphrase}</p>
                    <button type="button" class="button apply-suggestion" data-target="keyphrase">
                        Apply
                    </button>
                </div>
            `;

            $('.wpwps-seo-results').html(html);
        }
    };

    $(document).ready(() => WPWPSSEO.init());

})(jQuery);
