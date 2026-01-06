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
                $msg.css('color', '#166534').text(r.data.message);
                $('#ajanlatForm')[0].reset();
            } else {
                $msg.css('color', '#d32f2f').text(r.data.message);
            }
            $btn.prop('disabled', false).text('Küldés');
        });
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
});