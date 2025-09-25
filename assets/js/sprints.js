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
    })();
})