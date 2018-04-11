var setup_metadata_disclosure = function(){
    $("ul.metadata_container").hide();
    $(".disclosure_button").off("click").on("click",
        function(){
            var el = $(this);
            var container = el.parentsUntil("div").siblings(".metadata_container");
            if(el.hasClass("dc_up")) {
                //view is rolled up and hidden
                el.removeClass("dc_up").addClass("dc_down");
                container.slideDown(200);
            }else {
                //view is open and visible
                el.removeClass("dc_down").addClass("dc_up");
                container.slideUp(200);
            }
        }
    );

};

var setup_tree_data = function(){
    $(".tree_holder").each(
        function(index, el){
            if($(el).find("ul.ui-fancytree").length == 0) {
                var el_id = $(el).prop("id").replace("tree_","");
                $(el).fancytree(
                    {
                        checkbox:true,
                        selectMode: 3,
                        activate: function(event, data){

                        },
                        select: function(event, data){
                            var dl_button = $(event.target).parent().find("#dl_button_container_" + el_id);
                            var tree = $(el).fancytree("getTree");
                            // var fileSizes = get_file_sizes($(el));
                            var topNode = tree.getRootNode();
                            var dataNode = topNode.children[0];
                            var fileSizes = get_selected_files($(el));
                            if(fileSizes != null) {
                                var totalSizeText = myemsl_size_format(fileSizes.total_size);
                                var selectCount = Object.keys(fileSizes.sizes).length;
                            }

                        },
                        keydown: function(event, data){
                            if(event.which === 32) {
                                data.node.toggleSelected();
                                return false;
                            }
                        },
                        lazyLoad: function(event, data){
                            var node = data.node;
                            data.result = {
                                url: base_url + "status_api/get_lazy_load_folder",
                                data: {mode: "children", parent: node.key},
                                method:"POST",
                                cache: false,
                                complete: function(xhrobject,status){
                                    setup_file_download_links($(el));
                                }
                            };
                        },
                        loadChildren: function(event, ctx) {
                            ctx.node.fixSelection3AfterClick();
                        },
                        cookieId: "fancytree_tx_" + el_id,
                        idPrefix: "fancytree_tx_" + el_id + "-"
                    }
                );
            }
        }
    );
};
