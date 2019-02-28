$("form").submit(function() {
    triggerSearch();

    return false;
});

updateFavourites();

function updateFavourites() {

    $.each($(".favourite"), function() {
        var id = $(this).data("id");
        $(this).toggleClass("active", favourites.indexOf(id) !== -1);
    });
}

$("#results").on("click", ".favourite",function() {
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

var adjustedText = false;
var date = new Date();
var lastInput = date.getTime();
var lastSearch = date.getTime();
var searchContent;

datasources['conference'] = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.whitespace,
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
        url: Routing.generate('conference_search', { 'query': 'query' }),
        wildcard: 'query',
        transform: function(data) {
            return data;
        }
    },
    limit: 20
});

datasources['conference_date'] = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.whitespace,
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
        url: Routing.generate('conference_search', { 'query': 'query', 'type': 'date' }),
        wildcard: 'query',
        transform: function(data) {
            return data;
        }
    },
    limit: 20
});

datasources['conference_location'] = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.whitespace,
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
        url: Routing.generate('conference_search', { 'query': 'query', 'type': 'location' }),
        wildcard: 'query',
        transform: function(data) {
            return data;
        }
    },
    limit: 20
});

$(".conference-typeahead").typeahead({
        hint: true,
        highlight: true,
        minLength: 1
    },
    {
        limit: 20,
        name: 'conference_search',
        displayKey: 'code',
        templates: {
            suggestion: function(data) {
                return '<p><strong>[' + data.code + ']</strong> ' + data.name + '</p>';
            },
        },
        valueKey: 'id',
        source: datasources['conference']
    }).on('typeahead:selected', function(evt, item) {
    $(".conference-date-typeahead").val(item.date);
    $(".conference-location-typeahead").val(item.location);
});

$(".conference-date-typeahead").typeahead({
        hint: true,
        highlight: true,
        minLength: 1
    },
    {
        limit: 20,
        name: 'conference_date',
        displayKey: 'date',
        valueKey: 'id',
        source: datasources['conference_date']
    });

$(".conference-location-typeahead").typeahead({
        hint: true,
        highlight: true,
        minLength: 1
    },
    {
        limit: 20,
        name: 'conference_location',
        displayKey: 'location',
        valueKey: 'id',
        source: datasources['conference_location']
    });


$("form input").on("keyup", function(){
    if ($("form").serialize() !== searchContent) {
        adjustedText = true;
    }
    date = new Date();
    lastInput = date.getTime();
}).on("change", function(){
    if ($("form").serialize() !== searchContent) {
        adjustedText = true;
    }
    date = new Date();
    lastInput = date.getTime();
}).on("blur", function(){
    if ($("form").serialize() !== searchContent) {
        adjustedText = true;
    }
    date = new Date();
    lastInput = date.getTime();
});

setInterval(function() {
    date = new Date();
    if (adjustedText && (date.getTime() - lastInput > 600) && (date.getTime() - lastSearch > 600)) {
        triggerSearch();
    }
},200);

function triggerSearch() {
    adjustedText = false;
    lastSearch = date.getTime();
    $("#searching").toggle(true);
    $("#no-results").toggle(false);
    $("#results").empty();
    searchContent = $("form").serialize();
    $.post(Routing.generate('search'), searchContent, function(content){
        $("#searching").toggle(false);
        var results = $("#results");
        favourites = content.favourites;
        results.empty();
        $.each(content.results, function(key,item) {
            results.append("<li><a data-id=\"" + item.id + "\" class=\"favourite favourite" + item.id + "\" href=\"javascript:void(0)\"><i class=\"far fa-star\"></i></a><a class=\"item\" href=\'" + Routing.generate("reference_show", {"id": item.id }) + "'>" + item.name + "</a></li>");
        });
        updateFavourites();
        $("#no-results").toggle(content.results.length == 0);
        $("#some-results").toggle(content.results.length != 0);

        $(".show-total").toggle(content.results.length < content.total);
        $(".show-total #current-total").text(content.results.length);
        $(".show-total #overall-total").text(content.total);


    },"json");

    gtag('event', 'Search');
}