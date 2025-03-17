class ExportManager {
    constructor() {
        this.bindEvents();
        this.initializeDatePickers();
        this.initializeSelect2();
    }

    bindEvents() {
        $('#export-form').on('submit', (e) => {
            e.preventDefault();
            this.handleExport();
        });

        $('.export-type').on('change', (e) => {
            this.updateFilterOptions($(e.target).val());
        });

        $('.preview-export').on('click', (e) => {
            e.preventDefault();
            this.previewExport();
        });
    }

    initializeDatePickers() {
        $('.date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: new Date(),
            changeMonth: true,
            changeYear: true
        });
    }

    initializeSelect2() {
        $('.select2-field').select2({
            width: '100%',
            placeholder: 'Select options',
            allowClear: true
        });
    }

    handleExport() {
        const formData = new FormData($('#export-form')[0]);
        formData.append('action', 'wpwps_export_data');
        formData.append('nonce', wpwpsAdmin.nonce);

        this.showProgress();

        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: () => {
                const xhr = new window.XMLHttpRequest();
                xhr.addEventListener('progress', (evt) => {
                    if (evt.lengthComputable) {
                        const percentComplete = (evt.loaded / evt.total) * 100;
                        this.updateProgress(percentComplete);
                    }
                });
                return xhr;
            },
            success: (response) => {
                if (response.success) {
                    this.downloadFile(response.data.url, response.data.filename);
                } else {
                    this.showError(response.data.message);
                }
            },
            error: (xhr, status, error) => {
                this.showError('Export failed: ' + error);
            },
            complete: () => {
                this.hideProgress();
            }
        });
    }

    updateFilterOptions(exportType) {
        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_get_export_filters',
                type: exportType,
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderFilterOptions(response.data);
                }
            }
        });
    }

    previewExport() {
        const formData = new FormData($('#export-form')[0]);
        formData.append('action', 'wpwps_preview_export');
        formData.append('nonce', wpwpsAdmin.nonce);

        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    this.showPreviewModal(response.data);
                }
            }
        });
    }

    showPreviewModal(data) {
        const modal = new PreviewModal({
            title: 'Export Preview',
            data: data,
            onConfirm: () => this.handleExport()
        });
        modal.show();
    }

    showProgress() {
        $('.export-progress').show();
        $('.export-actions').hide();
    }

    hideProgress() {
        $('.export-progress').hide();
        $('.export-actions').show();
    }

    updateProgress(percentage) {
        $('.progress-bar').css('width', percentage + '%');
        $('.progress-text').text(Math.round(percentage) + '%');
    }

    downloadFile(url, filename) {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    showError(message) {
        const notification = new NotificationManager();
        notification.show('error', message);
    }

    renderFilterOptions(filters) {
        const container = $('.filter-options');
        container.empty();

        filters.forEach(filter => {
            const filterHtml = this.createFilterElement(filter);
            container.append(filterHtml);
        });

        this.initializeDatePickers();
        this.initializeSelect2();
    }

    createFilterElement(filter) {
        switch (filter.type) {
            case 'date':
                return this.createDateFilter(filter);
            case 'select':
                return this.createSelectFilter(filter);
            case 'text':
                return this.createTextFilter(filter);
            default:
                return '';
        }
    }
}

class PreviewModal {
    constructor(config) {
        this.config = config;
        this.createModal();
    }

    createModal() {
        this.modal = $(`
            <div class="preview-modal">
                <div class="preview-modal-content">
                    <div class="preview-modal-header">
                        <h3>${this.config.title}</h3>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="preview-modal-body">
                        ${this.renderPreviewTable()}
                    </div>
                    <div class="preview-modal-footer">
                        <button class="button button-secondary cancel-export">Cancel</button>
                        <button class="button button-primary confirm-export">Export</button>
                    </div>
                </div>
            </div>
        `);

        this.bindModalEvents();
    }

    renderPreviewTable() {
        if (!this.config.data.length) {
            return '<p>No data to preview</p>';
        }

        const headers = Object.keys(this.config.data[0]);
        const rows = this.config.data.slice(0, 5); // Show first 5 rows

        return `
            <div class="preview-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            ${headers.map(header => `<th>${header}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(row => `
                            <tr>
                                ${headers.map(header => `<td>${row[header]}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                ${this.config.data.length > 5 ? `
                    <p class="preview-note">Showing 5 of ${this.config.data.length} records</p>
                ` : ''}
            </div>
        `;
    }

    bindModalEvents() {
        this.modal.find('.close-modal, .cancel-export').on('click', () => this.hide());
        this.modal.find('.confirm-export').on('click', () => {
            this.hide();
            if (this.config.onConfirm) {
                this.config.onConfirm();
            }
        });
    }

    show() {
        $('body').append(this.modal);
        setTimeout(() => this.modal.addClass('show'), 10);
    }

    hide() {
        this.modal.removeClass('show');
        setTimeout(() => this.modal.remove(), 300);
    }
}

// Initialize export manager
jQuery(document).ready(function($) {
    window.exportManager = new ExportManager();
});