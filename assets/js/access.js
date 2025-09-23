$(document).ready(function() {
    const $page = $('#accessPage');
    const listUrl = $page.data('list-url');
    const inviteUrl = $page.data('invite-url');

    function loadList(q = '') {
        const $membersTable = $('#membersTable');
        $membersTable.html('<div class="p-4 text-center text-muted">Loading…</div>');
        $.get(listUrl, {q}, html => $membersTable.html(html));
    }

    loadList();

    // search (debounce)
    let t = null;
    $('#memberSearch').on('input', function () {
        clearTimeout(t);
        const q = this.value.trim();
        t = setTimeout(() => loadList(q), 250);
    });

    // toggle invite box
    $('#inviteOpen').on('click', () => $('#inviteBox').toggleClass('d-none'));

    // invite submit
    $(document).on('submit', '#inviteForm', function (e) {
        e.preventDefault();
        const $f = $(this), $btn = $f.find('button[type=submit]'), $err = $('#inviteError');

        $.ajax({
            url: inviteUrl,
            method: 'POST',
            data: $f.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.ok) {
                    $('#membersTable').html(res.html);
                    $f[0].reset();
                    $('#inviteBox').addClass('d-none');
                } else {
                    $err.removeClass('d-none').text(res.message || 'Invite failed.');
                }
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Invite failed.';
                $err.removeClass('d-none').text(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).text('Send invite');
            }
        });
    });

    // role change
    $(document).on('change', '.js-role-select', function () {
        const $row = $(this).closest('.member-row');
        $.ajax({
            url: $row.data('role-url'),
            method: 'POST',
            dataType: 'json',
            data: {
                _token: $row.data('token'),
                role: $(this).val()
            },
            error: xhr => {
                alert(xhr.responseJSON?.message || 'Failed to change role.');
                loadList($('#memberSearch').val().trim());
            }
        });
    });

    // remove (confirm modal)
    let rm = null;
    $(document).on('click', '.js-remove-member', function () {
        const $row = $(this).closest('.member-row');
        rm = {
            $row, url: $row.data('remove-url'), token: $row.data('token'),
            name: $row.find('td:first .fw-semibold').text().trim()
        };
        $('#rmName').text(rm.name);
        bootstrap.Modal.getOrCreateInstance('#removeMemberModal').show();
    });

    $(document).on('click', '#confirmRemoveBtn', function () {
        if (!rm) return;

        $.ajax({
            url: rm.url,
            method: 'POST',
            data: { _token: rm.token },
            dataType: 'json',
            success: function (res) {
                if (res.ok) {
                    $('#membersTable').html(res.html);

                    const modalEl = document.getElementById('removeMemberModal');
                    (bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl)).hide();

                    rm = null;
                } else {
                    alert(res.message || 'Remove failed.');
                }
            },
            error: function (xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Remove failed.');
            }
        });
    });
});