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
                        highlightRow(res.currentStatus, 'flash-save');
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
        const $btn   = $(this);
        const url    = $btn.data('url');
        const check  = $btn.data('checkUrl');
        const $row   = $btn.closest('.status-row');

        if ($btn.hasClass('disabled') || $btn.attr('aria-disabled') === 'true') {
            const t = $btn.attr('title') || 'Action not allowed.';
            alert(t);
            return;
        }

        const count = $('#statusesList .status-row').length;
        if (count <= 2) return;

        deleteTarget = { url, $row };

        $.get(check)
            .done(function (res) {
                if (res && res.canDelete) {
                    $('#confirmDeleteModal').modal('show');
                    return;
                }

                if (res && res.html) {
                    $('#workflowModalContent').html(res.html);
                    $('#workflowModal').modal('show');
                } else {
                    alert(res?.message || 'Unable to prepare deletion.');
                }
            })
            .fail(function (xhr) {
                alert(xhr.responseJSON?.message || 'Unable to prepare deletion.');
            });
    });

    $(document).on('click', '#confirmDeleteBtn', function () {
        if (!deleteTarget) return;

        const count = $('#statusesList .status-row').length;
        if (count <= 2) return;

        $.ajax({
            url: deleteTarget.url,
            method: 'POST',
            dataType: 'json'
        })
            .done(function (res) {
                if (res && res.ok) {
                    if (res.html) {
                        $('#statusesList').html(res.html);
                    }
                    $('#confirmDeleteModal').modal('hide');
                    deleteTarget = null;
                } else {
                    alert(res?.message || 'Delete failed.');
                }
            })
            .fail(function (xhr) {
                alert(xhr.responseJSON?.message || 'Delete failed.');
            });
    });

    $(document).on('click', '#confirmReassignDeleteBtn', function () {
        if (!deleteTarget) return;
        const $form   = $('#statusReassignForm');
        const postUrl = $form.data('postUrl');
        const token   = $form.data('token');
        const target  = $form.find('[name="targetStatusId"]').val();

        if (!target) {
            alert('Please choose a target status.');
            return;
        }

        $.ajax({
            url: postUrl,
            method: 'POST',
            dataType: 'json',
            data: { _token: token, targetStatusId: target }
        })
            .done(function (res) {
                if (res && res.ok) {
                    if (res.html) $('#statusesList').html(res.html);
                    $('#workflowModal').modal('hide');
                    deleteTarget = null;
                } else {
                    alert(res?.message || 'Reassign & delete failed.');
                }
            })
            .fail(function (xhr) {
                alert(xhr.responseJSON?.message || 'Reassign & delete failed.');
            });
    });

    $(function initWorkflowSorting() {
        const $wrap = $('#statusesList');
        if (!$wrap.length || typeof $.fn.sortable !== 'function') return;

        try { $wrap.sortable('destroy'); } catch (e) {}

        $wrap.sortable({
            items:        '> .status-row',
            handle:       '.drag-handle',
            axis:         'y',
            placeholder:  'status-placeholder',
            tolerance:    'pointer',
            forcePlaceholderSize: true,
            revert:       120,

            start: function (_e, ui) {
                ui.placeholder.height(ui.item.outerHeight());
            },

            update: function () {
                const url   = $wrap.data('sortUrl')  || $wrap.attr('data-sort-url');
                const csrf  = $wrap.data('csrf')     || $wrap.attr('data-csrf');
                const ids   = $wrap.children('.status-row').map(function () {
                    return $(this).data('status-id');
                }).get();

                if (!url || !ids.length) return;

                $.ajax({
                    url: url,
                    method: 'POST',
                    dataType: 'json',
                    data: { _token: csrf, ids: ids }
                })
                    .done(function (res) {
                        if (!res || !res.ok) {
                            flash(res && res.message ? res.message : 'Sort failed.', 'danger');
                        } else {
                            flash('Order saved.', 'success');
                        }
                    })
                    .fail(function (xhr) {
                        (window.App?.ui?.flashError || alert)(xhr.responseJSON?.message || 'Sort failed.');
                    });
            }
        });
    });

    function highlightRow(id, className) {
        const row = $('.status-row[data-status-id="' + id + '"]');
        row.addClass(className);

        setTimeout(function () {
            row.removeClass(className);
        }, 1000);
    }

    function flash(msg, type) {
        var $box = $('<div class="alert alert-' + (type || 'info') + ' shadow mb-2 position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:1080;min-width:280px;">' + msg + '</div>');
        $('body').append($box);
        setTimeout(function(){ $box.fadeOut(180, function(){ $(this).remove(); }); }, 1800);
    }
});