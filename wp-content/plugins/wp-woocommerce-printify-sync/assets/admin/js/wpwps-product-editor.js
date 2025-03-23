(function($) {
    'use strict';

    const ProductAI = {
        init: function() {
            this.addGenerateButtons();
            this.initEventListeners();
        },

        addGenerateButtons: function() {
            // Add AI buttons next to title, description, and tags
            $('#title').after(this.createButton('title', 'Generate Title'));
            $('#excerpt').after(this.createButton('description', 'Generate Description'));
            $('.tagsdiv').append(this.createButton('tags', 'Generate Tags'));
        },

        createButton: function(type, text) {
            return $(`
                <button type="button" class="button wpwps-ai-generate" data-type="${type}">
                    <i class="fas fa-magic"></i> ${text}
                </button>
            `);
        },

        initEventListeners: function() {
            $('.wpwps-ai-generate').on('click', (e) => {
                const $btn = $(e.currentTarget);
                const type = $btn.data('type');
                
                $btn.prop('disabled', true)
                   .html('<i class="fas fa-spinner fa-spin"></i> Generating...');

                this.generateContent(type)
                    .then(response => {
                        if (response.success) {
                            this.updateContent(type, response.data);
                        } else {
                            WPWPS.toast.error(response.data.message);
                        }
                    })
                    .finally(() => {
                        $btn.prop('disabled', false)
                           .html(`<i class="fas fa-magic"></i> Generate ${type.charAt(0).toUpperCase() + type.slice(1)}`);
                    });
            });
        },

        generateContent: function(type) {
            return WPWPS.api.post('generate_product_content', {
                product_id: $('#post_ID').val(),
                type: type
            });
        },

        updateContent: function(type, data) {
            switch(type) {
                case 'title':
                    $('#title').val(data.title);
                    break;
                    
                case 'description':
                    if (tinymce.get('content')) {
                        tinymce.get('content').setContent(data.description);
                    } else {
                        $('#content').val(data.description);
                    }
                    break;
                    
                case 'tags':
                    const tagBox = window.tagBox;
                    const tagsInput = $('#tax-input-product_tag');
                    data.tags.forEach(tag => {
                        if (!tagsInput.val().includes(tag)) {
                            tagBox.flushTags($('#product_tag'), tag);
                        }
                    });
                    break;
            }
            
            WPWPS.toast.success(`Generated ${type} successfully`);
        }
    };

    $(document).ready(() => ProductAI.init());

})(jQuery);
