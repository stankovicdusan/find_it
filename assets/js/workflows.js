$(document).ready(function () {
    $('.open-modal').on('click', function () {
        const url = $(this).data('url');
        const $modal = $('#workflowModal');
        const $content = $('#workflowModalContent');

        // Show loader
        $content.html(`
            <div class="modal-body text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `);

        $modal.modal('show');

        $.ajax({
            url: url,
            method: 'GET',
            success: function (html) {
                $content.html(html);
            },
            error: function () {
                $content.html('<div class="p-4 text-danger">Failed to load form.</div>');
            }
        });
    });
});