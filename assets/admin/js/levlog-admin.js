
$ = jQuery;


$(document).ready( function () {

    new DataTable('#liveu_events_table', {

        ajax: {
            url: '/wp-admin/admin-ajax.php',
            dataSrc: 'data.data',
            data: function (d) {
                d.action = 'get_events_list';
                d.nonce_code = myajax.nonce
            }
        },
        processing: true,
        serverSide: true,

        columns: [
            {
                data: "id",
                visible: true
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
        responsive: {
            details: {
                display: DataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'Details for ' + data[0] + ' ' + data[1];
                    }
                }),
                renderer: DataTable.Responsive.renderer.tableAll()
            }
        }
    });



} );
