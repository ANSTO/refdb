updateFavourites();

function updateFavourites() {

    $.each($(".favourite"), function() {
        var id = $(this).data("id");
        $(this).toggleClass("active", favourites.indexOf(id) !== -1);
    });
}

$(document).on("click", ".favourite",function() {
    var id = $(this).data("id");
    $(this).toggleClass("active", !(favourites.indexOf(id) !== -1));
    if (!(favourites.indexOf(id) !== -1)) {
        favourites.push(id);
    } else {
        favourites = favourites.filter(function(item){ return item !== id; })
    }
    toggleFavourite(id);
});

function toggleFavourite(id) {
    $.getJSON(Routing.generate("favourite_toggle", {"id": id}),
        "",
        function(data, textStatus, jqXHR){
            favourites = data.favourites;
            updateFavourites();
        });
}
