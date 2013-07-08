head.js(connect+'/js/connect.js')
    .js('resources/js/jquery.min.js')
.ready(function(){
head.js('resources/js/bootstrap.min.js')
    .js('resources/js/bootstrap.fileInput.js')
    .js('resources/js/onde.js')
    .ready(function(){
        Connect({
            onlogin: function(user) {
                if(!logged) {
                    $.post(base+'/login',JSON.stringify(user),function(){
                        location.reload();
                    });
                }
            },
            onlogout: function(nothing){
                if(logged) {
                    $.post(base+'/logout',nothing,function(){
                        location.reload();
                    });
                }
            }
        });
        $("#login a").click(function(){ Connect.login(); });
        $("#logout a").click(function(){ Connect.logout(); });
        $('form button[class*="btn-danger"]').each(function(i,e){
            $(e).parent().parent().submit(function(){
                return confirm("Confirma excluir esse recurso?");
            });
        });
        if(($("form#data").length >= 1)) {
            var form = new onde.Onde($("#data"));
            form.render(schema,data,{collapsedCollapsibles: true});
            $("#data").submit(function(e){
                e.preventDefault();
                var data = form.getData().data;
                $.post($("#data").attr("action"),JSON.stringify(data),function(r){
                    if(typeof r != "object") r = JSON.parse(r);
                    location.href=location.protocol+'//'+location.hostname+''+base+'/biblio/'+r._id;
                });
                return false;
            });
        }
        if($("html").attr("id") == "validate-page") {
            for(var i in schema.properties) {
                $("#field").append("<option>"+ schema.properties[i].label +"</option>");
            }
        }

    });
});
