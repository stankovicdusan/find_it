$(document).ready(function () {
    (function () {
        const $modal   = $('#ajaxModal');
        const $content = $('#ajaxModalContent');

        $(document).on('click', '.js-open-modal', function (e) {
            e.preventDefault();
            const url = $(this).data('url');

            $modal.modal('show');
            $.get(url)
                .done(html => $content.html(html))
                .fail(() => $content.html('<div class="p-4 text-danger">Failed to load form.</div>'));
        });

        $(document).on('submit', '.js-ajax-form', function (e) {
            e.preventDefault();
            const $f     = $(this);
            const url    = $f.attr('action');
            const method = $f.attr('method');
            const refreshTarget = $f.data('refreshTarget');

            $.ajax({ url, method, data: $f.serialize() })
                .done(res => {
                    if (res && res.ok) {
                        if (refreshTarget) {
                            const $wrap = $(refreshTarget);
                            const listUrl = $wrap.data('url');
                            if (listUrl) {
                                $.get(listUrl).done(html => $wrap.html(html));
                            }
                        }
                        $modal.modal('hide');
                    } else if (res && res.html) {
                        $content.html(res.html);
                    } else {
                        $content.html('<div class="p-4 text-danger">Action failed.</div>');
                    }
                })
                .fail(xhr => {
                    const msg = xhr.responseJSON?.message || 'Request failed.';
                    $content.html('<div class="p-4 text-danger">' + msg + '</div>');
                });
        });

        $(document).on('click', '.js-remove-sprint-ticket', function () {
            const $btn  = $(this);
            const url   = $btn.data('url');
            const token = $btn.data('token');

            const prev = $btn.html();

            $.ajax({ url, method: 'POST', data: { _token: token } })
                .done(function (res) {
                    if (res && res.ok) {
                        if (res.html) {
                            $content.html(res.html);
                        }

                        const $wrap = $('#sprintsWrap');
                        const listUrl = $wrap.data('url');
                        if (listUrl) $.get(listUrl).done(html => $wrap.html(html));
                    } else {
                        const msg = res?.message || 'Remove failed.';
                        $('#ticketsModalAlert').removeClass('d-none').text(msg);
                        $btn.prop('disabled', false).html(prev);
                    }
                })
                .fail(function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Remove failed.';
                    $('#ticketsModalAlert').removeClass('d-none').text(msg);
                });
        });

        $(document).on('click', '.js-ticket-status-choice', function (e) {
            e.preventDefault();

            const $item = $(this);
            const toStatusId = parseInt($item.data('to-status'), 10);

            const $box = $item.closest('#ticketStatusBox');
            const ticketId = parseInt($box.data('ticket-id'), 10);
            const moveUrl = $box.data('move-url');
            const csrf = $box.data('csrf');

            $.ajax({
                url: moveUrl,
                method: 'POST',
                dataType: 'json',
                data: { _token: csrf, ticketId, toStatusId }
            })
                .done(function (res) {
                    if (!res || !res.ok) {
                        alert(res?.message || 'Update failed.');
                        return;
                    }

                    $('#completedTickets').html(res.htmlCompleted);
                    $('.completed-tickets').html(res.countOfCompletedTickets);
                    $('#backlogWrap').html(res.htmlOthers);

                    const $card  = $('.js-open-ticket[data-ticket-id="' + ticketId + '"]');
                    const modalUrl = $card.data('url');
                    if (modalUrl) {
                        $('#ticketModalContent').html(
                            '<div class="modal-body text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>'
                        );
                        $.get(modalUrl).done(html => $('#ticketModalContent').html(html));
                    }
                })
                .fail(function (xhr) {
                    alert(xhr.responseJSON?.message || 'Update failed.');
                });
        });

        $(document).on('click', '.js-ticket-change-assignee', function (e) {
            e.preventDefault();

            const $item = $(this);
            const userId = parseInt($item.data('user-id'), 10);

            const $box = $item.closest('#ticketAssigneeBox');
            const ticketId = parseInt($box.data('ticket-id'), 10);
            const changeUrl = $box.data('change-url');
            const csrf = $box.data('csrf');

            $.ajax({
                url: changeUrl,
                method: 'POST',
                dataType: 'json',
                data: { _token: csrf, ticketId, userId }
            })
                .done(function (res) {
                    if (!res || !res.ok) {
                        alert(res?.message || 'Update failed.');
                        return;
                    }

                    $('#backlogWrap').html(res.html);

                    const $card  = $('.js-open-ticket[data-ticket-id="' + ticketId + '"]');
                    const modalUrl = $card.data('url');
                    if (modalUrl) {
                        $('#ticketModalContent').html(
                            '<div class="modal-body text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>'
                        );
                        $.get(modalUrl).done(html => $('#ticketModalContent').html(html));
                    }
                })
                .fail(function (xhr) {
                    alert(xhr.responseJSON?.message || 'Update failed.');
                });
        });

        $(document).on('click', '.js-create-ticket', function () {
            const url = $(this).data('url');
            const $modal = $('#createTicketModal');
            const $content = $('#createTicketModalContent');

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

        $(document).on('submit', '#createTicketModalContent form', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $content = $('#createTicketModalContent');

            $.ajax({
                url: $form.attr('action'),
                method: $form.attr('method'),
                data: new FormData(this),
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.ok) {
                        $('#backlogWrap').html(res.ticketsHtml);

                        $('#createTicketModal').modal('hide');
                    } else if (res.html) {
                        $content.html(res.html);
                    } else {
                        $content.html('<div class="p-4 text-danger">Unexpected response.</div>');
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.html) {
                        $content.html(xhr.responseJSON.html);
                    } else {
                        $content.html('<div class="p-4 text-danger">An error occurred.</div>');
                    }
                }
            });
        });
    })();

    (function () {
        const $modal   = $('#ticketModal');
        const $content = $('#ticketModalContent');

        function setTicketParam(id) {
            const u = new URL(location.href);
            u.searchParams.set('ticket', id);
            history.pushState({}, '', u);
        }

        function removeTicketParam() {
            const u = new URL(location.href);
            u.searchParams.delete('ticket');
            history.replaceState({}, '', u);
        }

        function getTicketUrlById(id) {
            const $card = $('.js-open-ticket[data-ticket-id="' + id + '"]');
            if ($card.length) return $card.data('url');
        }

        function renderError() {
            $content.html('<div class="p-4 text-danger">Failed to load ticket.</div>');
        }

        function loadAndShow(id, url, pushParamAfterSuccess = true) {
            if (!url) return;

            $modal.modal('show');

            $.get(url)
                .done(function (html) {
                    $content.html(html);

                    if (pushParamAfterSuccess) setTicketParam(id);
                })
                .fail(renderError);
        }

        function openTicketById(id) {
            loadAndShow(id, getTicketUrlById(id));
        }

        $(document)
            .off('click.ticketModal', '.js-open-ticket')
            .on('click.ticketModal', '.js-open-ticket', function (e) {
                if ($('.ui-sortable-helper').length) return;
                e.preventDefault();

                const id = String($(this).data('ticket-id'));
                const url = $(this).data('url');
                loadAndShow(id, url);
            });

        $(function () {
            const id = new URL(location.href).searchParams.get('ticket');
            if (id) openTicketById(id);
        });

        $modal.on('hidden.bs.modal', removeTicketParam);
    })();
})