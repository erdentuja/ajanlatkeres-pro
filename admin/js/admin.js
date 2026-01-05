jQuery(function($){
    // Backend Admin: Státusz váltás
    $('.ak-status-select').on('change', function(){
        var $select = $(this);
        var $wrapper = $select.closest('.status-wrapper');
        var $indicator = $wrapper.find('.save-indicator');
        
        $select.prop('disabled', true);
        $indicator.css('opacity', 0);

        $.ajax({
            url: ajaxurl, // WordPress define
            type: 'POST',
            data: {
                action: 'ak_update_status',
                id: $select.data('id'),
                status: $select.val(),
                nonce: akAdminAjax.nonce
            },
            success: function(r){
                $select.prop('disabled', false);
                if(r.success){
                    $indicator.css('opacity', 1).css('color', '#166534'); // Zöld pipa
                    setTimeout(function(){ $indicator.css('opacity', 0); }, 2000);
                    
                    // Szín frissítése (opcionális, ha a select osztálya függ tőle)
                    $select.removeClass('status-uj status-feldolgozva').addClass('status-' + $select.val());
                } else {
                    alert('Hiba: ' + (r.data.message || 'Ismeretlen hiba'));
                    location.reload();
                }
            },
            error: function(){
                $select.prop('disabled', false);
                alert('Szerver hiba.');
            }
        });
    });
});
