var get_doi_release_data = function() {
    var release_check_url = base_url + "ajax_api/get_release_states";
    var my_transactions = $(".fieldset_container .transaction_identifier").map(function() {
        return $(this).val();
    }).toArray();
    $.post(
        release_check_url, JSON.stringify(my_transactions)
    )
        .done(
            function(data) {
                if (data) {
                    add_doi_notations(data);
                }
            }
        )
        .fail(
            function(jqxhr, error, message) {
                alert("A problem occurred getting release data for DOIs.\n[" + message + "]");
            }
        );

};



var add_doi_notations = function(metadata_object) {
    $.each(metadata_object, function(index, item) {
        var upload_item_container = $("#fieldset_container_" + index);

    });
};
