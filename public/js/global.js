// Ajax modal window
$(document).on("click", ".btn-modal", function() {


    var $ajaxModal = $("#ajaxModal");

    if($(this).hasClass("btn-modal-lg")) {
        $ajaxModal.find(".modal-dialog").addClass("modal-lg");
    } else {
        $ajaxModal.find(".modal-dialog").removeClass("modal-lg");
    }

    if ($(this)[0].tagName === "A") {
        console.log($(this).attr("href"));
        $ajaxModal.data("href", $(this).attr("href"));
    } else {
        $ajaxModal.data("href", $(this).data("href"));
    }

    $ajaxModal.data("reload", $(this).data("reload"));

    if (($ajaxModal.data('bs.modal') || {})._isShown) {
        ajaxModalShow($ajaxModal);
    } else {
        $ajaxModal.modal('show');
    }

    return false;
});

var ajaxModalShow = function($container) {
    $('.modal-content').html('<div class="modal-body text-center"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>');
    $.get({
        url: $container.data("href"),
        success: function(response) {
            $container.find(".modal-content").html(response);
            $container.find(".modal-content form").on("submit", ajaxForm);
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            $container.find(".modal-content").html('<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title text-danger"><i class="material-icons">error</i> Something went wrong</h4></div>' +
                '<div class="modal-body"><p>An error occurred trying to perform that task.</p><p>Try reloading the page and try again. If the problem persists, contact the service desk on x9200</p></div>' +
                '<div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div>' +
                '</div>')
        }
    });
};

var ajaxModalHide = function($container) {
    if($container.data("reload") !== undefined && $container.data("reload") !== "") {
        if($container.data("reload") === "reload") {
            location.reload(true);
        }
    }
    $(this).data("reload", "");
};

var ajaxForm = function () {
    var container = $(this);
    $(this).find('.btn').attr('disabled', true);
    $.post({
        url: $(this).attr("action"),
        data: $(this).serialize(),
        success: function (response, status, xhr) {

            var ct = xhr.getResponseHeader("content-type") || "";
            if (ct.indexOf('html') > -1) {
                var wrapper = container.closest(".modal-content");
                wrapper.html(response);
                wrapper.find("form").on("submit", ajaxForm);
                wrapper.find('.btn').attr('disabled', false);
            }
            if (ct.indexOf('json') > -1) {
                if (response.success) {
                    if (typeof response.redirect !== undefined) {
                        location.href = response.redirect;
                    }
                }
            }

        }
    }).fail(function () {
        container.closest(".modal-content").html('<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title text-danger"><i class="material-icons">error</i> Something went wrong</h4></div>' +
            '<div class="modal-body"><p>An error occurred trying to perform that task.</p><p>Try reloading the page and try again. If the problem persists, contact the service desk on x9200</p></div>' +
            '<div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div>' +
            '</div>');
    });
    return false;
};

$('.ajaxModal')
    .on('show.bs.modal', function() {ajaxModalShow($(this))})
    .on('hide.bs.modal', function() {ajaxModalHide($(this))});


if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
        position = position || 0;
        return this.indexOf(searchString, position) === position;
    };
}

if (!String.prototype.endsWith) {
    String.prototype.endsWith = function(search, this_len) {
        if (this_len === undefined || this_len > this.length) {
            this_len = this.length;
        }
        return this.substring(this_len - search.length, this_len) === search;
    };
}

$(document).ready(function(){
    $("#filter").submit(function(){
        var val = $("#filterValue").val();
        if (!val.endsWith('*'))
            val = val + "*" ;

        if (!val.startsWith('*'))
            val = "*" + val;

        $("#filterValue").val(val);
    });

    $('textarea').each(function () {
        this.setAttribute('style', 'height:' + (this.scrollHeight + 10) + 'px;');
    }).on('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

$('input[type=file]').on('change',function(e){
    var fileName = e.target.files[0].name;
    $(this).closest(".custom-file").find('.custom-file-label').html(fileName);
});