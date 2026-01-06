jQuery(function ($) {
    // Delegált eseménykezelés a gombokhoz (hogy AJAX után is működjenek)

    // Számlálók kezelése
    $(document).on('click', '.ak-counter .plus', function (e) {
        e.preventDefault();
        let $input = $(this).prev('input');
        let val = parseInt($input.val()) || 0;
        $input.val(val + 1);
    });

    $(document).on('click', '.ak-counter .minus', function (e) {
        e.preventDefault();
        let $input = $(this).next('input');
        let val = parseInt($input.val()) || 0;
        if (val > 0) $input.val(val - 1);
    });

    // Űrlap beküldése
    $('#ajanlatForm').on('submit', function (e) {
        e.preventDefault();
        let $btn = $(this).find('.ak-submit');
        let $msg = $(this).find('.ak-msg');

        $btn.prop('disabled', true).text('Küldés...');

        $.post(akAjax.url, $(this).serialize() + '&action=ajanlat_submit&nonce=' + akAjax.nonce, function (r) {
            if (r.success) {
                // Siker esetén Modal megjelenítése
                console.log('AJAX Success: Modal showing...');
                $('#ajanlatForm')[0].reset();

                // Debug info
                var $modal = $('#ak-success-modal');
                console.log('AJAX Success. Modal found:', $modal.length);

                // CSS osztály alapú megjelenítés (elkerüli a jQuery/CSS konfliktust)
                $modal.css({
                    'display': 'flex',
                    'z-index': '10000'
                }).addClass('show');

            } else {
                $msg.css('color', '#d32f2f').text(r.data.message);
                $btn.prop('disabled', false).text('Küldés');
            }
        });
    });

    // Siker Modal - Rendben gomb (Vissza a főoldalra)
    $(document).on('click', '.ak-redirect-home', function (e) {
        e.preventDefault();
        window.location.href = window.location.origin;
    });

    // Frontend Admin: Státusz kapcsoló (Toggle) dinamikus felirattal
    $(document).on('change', '.ak-fe-toggle', function () {
        let $this = $(this);
        let isChecked = $this.is(':checked');
        let status = isChecked ? 'feldolgozva' : 'uj';
        let id = $this.data('id');
        let $label = $this.closest('.status-wrapper').find('.ak-status-label');

        // UI frissítése azonnal (visszajelzés a felhasználónak)
        if (isChecked) {
            $label.text('Feldolgozva').removeClass('status-label-uj').addClass('status-label-feldolgozva');
        } else {
            $label.text('Új').removeClass('status-label-feldolgozva').addClass('status-label-uj');
        }

        $this.css('opacity', '0.5').prop('disabled', true);

        $.ajax({
            url: akAjax.url,
            type: 'POST',
            data: {
                action: 'ak_update_status',
                id: id,
                status: status,
                nonce: akAjax.status_nonce,
                fe_pass: (typeof ak_fe_pass !== 'undefined' ? ak_fe_pass : ''),
                is_fe: 1
            },
            beforeSend: function () {
                // Ha se nonce, se jelszó, akkor hiba
                if (!akAjax.status_nonce && typeof ak_fe_pass === 'undefined') {
                    alert('Ehhez a művelethez be kell jelentkeznie!');
                    $this.css('opacity', '1').prop('disabled', false);
                    // Visszaállítás
                    $this.prop('checked', !isChecked);
                    return false;
                }
            },
            success: function (r) {
                $this.css('opacity', '1').prop('disabled', false);
                if (!r.success) {
                    // Hiba esetén visszamenőleges váltás
                    alert('Hiba történt a mentés során.');
                    location.reload();
                }
            },
            error: function () {
                $this.css('opacity', '1').prop('disabled', false);
                alert('Szerver hiba történt.');
                location.reload();
            }
        });
    });

    // Frontend Admin: Törlés
    $(document).on('click', '.ak-fe-delete', function (e) {
        e.preventDefault();
        let id = $(this).data('id');
        let $row = $('#ak-row-' + id);

        if (!confirm('Biztosan törli ezt az ajánlatot?')) return;

        $(this).prop('disabled', true);

        if (!akAjax.admin_nonce && typeof ak_fe_pass === 'undefined') {
            alert('Ehhez a művelethez be kell jelentkeznie!');
            $(this).prop('disabled', false);
            return;
        }

        $.post(akAjax.url, {
            action: 'ak_fe_delete',
            id: id,
            nonce: akAjax.admin_nonce,
            fe_pass: (typeof ak_fe_pass !== 'undefined' ? ak_fe_pass : '')
        }, function (r) {
            if (r.success) {
                $row.css('background', '#fee2e2').fadeOut(400, function () { $(this).remove(); });
            } else {
                alert('Nincs jogosultsága a törléshez, vagy a munkamenet lejárt.');
                $(this).prop('disabled', false);
            }
        });
    });

    // --- Frontend Admin: Szerkesztés Modal --- //

    // Megnyitás
    $(document).on('click', '.ak-fe-edit', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var $btn = $(this);
        $btn.prop('disabled', true).text('...');

        $.post(akAjax.url, {
            action: 'ak_fe_get_details',
            id: id,
            fe_pass: (typeof ak_fe_pass !== 'undefined' ? ak_fe_pass : '')
        }, function (r) {
            $btn.prop('disabled', false).text('✏️');
            if (r.success) {
                var d = r.data;
                // Form kitöltése
                $('#edit-id').val(d.id);
                $('#edit-name').val(d.name);
                $('#edit-email').val(d.email);
                $('#edit-phone').val(d.phone);
                $('#edit-arrival').val(d.arrival);
                $('#edit-rooms').val(d.rooms);
                $('#edit-nights').val(d.nights);
                $('#edit-adults').val(d.adults);
                $('#edit-children').val(d.children);
                $('#edit-package').val(d.package);
                $('#edit-note').val(d.note);

                // Modal megjelenítés
                $('#ak-edit-modal').css('display', 'flex').hide().fadeIn(200, function () {
                    $(this).addClass('show');
                });
            } else {
                alert('Hiba: ' + (r.data.message || 'Adatlekérés sikertelen'));
            }
        });
    });

    // Bezárás
    $(document).on('click', '.ak-modal-close, .ak-modal-close-btn', function () {
        $('#ak-edit-modal').removeClass('show').fadeOut(200);
    });

    // Bezárás ha a háttérre kattint - KIKAPCSOLVA USER KÉRÉSRE (2025-01-06)
    // $(window).on('click', function (e) {
    //     if ($(e.target).is('#ak-edit-modal')) {
    //         $('#ak-edit-modal').removeClass('show').fadeOut(200);
    //     }
    // });

    // Mentés
    $('#ak-edit-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('.ak-submit');
        $btn.prop('disabled', true).text('Mentés...');

        var data = $form.serializeArray();
        data.push({ name: 'action', value: 'ak_fe_save_details' });
        data.push({ name: 'fe_pass', value: (typeof ak_fe_pass !== 'undefined' ? ak_fe_pass : '') });

        $.post(akAjax.url, data, function (r) {
            $btn.prop('disabled', false).text('Mentés');
            if (r.success) {
                // Siker: Bezárás és újratöltés, hogy a táblázat frissüljön
                $('#ak-edit-modal').removeClass('show').fadeOut(200, function () {
                    alert('Sikeres mentés!');
                    location.reload();
                });
            } else {
                alert('Hiba: ' + (r.data.message || 'Mentés sikertelen'));
            }
        });
    });

});