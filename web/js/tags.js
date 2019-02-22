var elts = $('input.entity-tags');


datasources = [];

$.each(elts, function(index, elt) {

    var $elt = $(elt);

    var source = $elt.data("source");

    if (typeof datasources[source] === 'undefined') {
        datasources[source] = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: Routing.generate(source, { 'query': 'query' }),
                wildcard: 'query',
                transform: function(data) {
                    return data;
                }
            },
            limit: 5
        });
        datasources[source].initialize();
    }

    element = $elt.tagsinput({
        itemValue: function(item) {
            return item.id;
        },
        itemText: function(item) {
            return item.name;
        },
        typeaheadjs: {
            name: source,
            displayKey: 'name',
            valueKey: 'id',
            source: datasources[source].ttAdapter()
        }
    });

    var data;
    if ($elt.val() === "") {
        data = [];
    } else {
        data = JSON.parse($elt.val());
    }

    $.each(data, function(i,v) {
        $elt.tagsinput('add', v);
    });
});