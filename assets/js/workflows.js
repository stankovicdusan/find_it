$(document).ready(function () {
    $(document).on('click', '.open-modal', function () {
        const url = $(this).data('url');
        const $modal = $('#workflowModal');
        const content = $('#workflowModalContent');

        content.html(`
            <div class="modal-body text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `);

        $modal.modal('show');

        $.ajax({
            url: url,
            method: 'GET',
            success: function (html) {
                content.html(html);
            },
            error: function () {
                content.html('<div class="p-4 text-danger">Failed to load form.</div>');
            }
        });
    });

    $(document).on('submit', '#workflowModalContent form', function (e) {
        e.preventDefault();
        const form = $(this);
        const content = $('#workflowModalContent');

        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: {
                'Accept': 'application/json'
            },
            success: function (res) {
                if (res.ok) {
                    if (res.html) {
                        $('#statusesList').html(res.html);
                        highlightRow(res.currentStatus);
                    }

                    $('#workflowModal').modal('hide');
                    return;
                }

                if (res.html) {
                    content.html(res.html);
                } else {
                    content.html('<div class="p-4 text-danger">Unexpected response.</div>');
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.html) {
                    content.html(xhr.responseJSON.html);
                } else {
                    content.html('<div class="p-4 text-danger">An error occurred.</div>');
                }
            }
        });
    });

    let deleteTarget = null;

    $(document).on('click', '.js-delete-status', function () {
        const count = $('#statusesList .status-row').length;

        if (count <= 2) {
            return;
        }

        deleteTarget = {
            url: $(this).data('url'),
            row: $(this).closest('.status-row')
        };

        $('#confirmDeleteModal').modal('show');
    });

    $(document).on('click', '#confirmDeleteBtn', function () {
        if (!deleteTarget) return;

        const count = $('#statusesList .status-row').length;

        if (count <= 2) {
            return;
        }

        $.ajax({
            url: deleteTarget.url,
            method: 'POST',
            dataType: 'json',
            success: function (resp) {
                const row = deleteTarget.row;

                row.addClass('flash-delete');
                setTimeout(function () {
                    row.slideUp(250, function () { $(this).remove(); });
                }, 1000);

                $('#confirmDeleteModal').modal('hide');
                deleteTarget = null;
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Delete failed.';
                alert(msg);
            }
        });
    });

    function highlightRow(id) {
        const row = $('.status-row[data-status-id="' + id + '"]');
        row.addClass('flash-save');

        setTimeout(function () {
            row.removeClass('flash-save');
        }, 1000);
    }
});