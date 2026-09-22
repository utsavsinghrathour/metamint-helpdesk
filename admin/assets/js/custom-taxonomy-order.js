jQuery(document).ready(function($) {
    $('#the-list tr').css('cursor','move');
    $('#the-list').sortable({
        update: function(event, ui) {
            
            var termOrder = [];
            $('#the-list tr').each(function() {
                var id = $(this).prop('id').replace('tag-', ''); 
                termOrder.push(id);
            });
           
            $.ajax({
                url: taxonomyOrderData.ajax_url,
                type: 'POST',
                data: {
                    action: 'mmhd_update_taxonomy_order',
                    order: termOrder,
                    nonce: taxonomyOrderData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Order updated successfully!');
                    } else {
                        console.log('Error updating order.');
                    }
                }
            });
        }
    });
});