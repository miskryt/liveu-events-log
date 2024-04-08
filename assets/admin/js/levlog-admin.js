
$ = jQuery;


$(document).ready( function () {

    let table = new DataTable('#liveu_events_table', {
        ajax: {
            url: '/wp-admin/admin-ajax.php',
            data: function (d) {
                d.action = 'get_data';
                d.nonce_code = myajax.nonce
            }
        },
        processing: true,
        serverSide: true,
        columns: [
            {
                data: "id",
                visible: false
            },
            {
                data: 'user',
                visible: true
            },
            {
                data: 'action'
            },
            {
                data: 'post_url'
            },
            {
                data: 'datetime'
            },
            {
                data: 'post_type'
            },
            {
                data: 'new',
                visible: false
            }
        ],
    });



} );
