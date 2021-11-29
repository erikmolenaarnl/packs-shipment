jQuery(function($) {
    $('.notice[data-packs-dismiss-nonce]').on('click', '.notice-dismiss', function() {
        var $notice = $(this).closest('.notice'),
            nonce = $notice.data('packs-dismiss-nonce'),
            id = $notice.attr('id');

        $.post(
            ajaxurl,
            {
                "action": 'packs_dismiss-' + id,
                "_ajax_nonce": nonce,
                "id": id,
                "notice-data": $notice.attr('data-packs-notice-data'), //Use $.attr() because it doesn't parse JSON.
                "signature": $notice.data('packs-signature')
            }
        );
    });
});