(function () {
    var module_routes = [
    {
        "uri": "freefeaturerequests\/ajax",
        "name": "freefeaturerequests.ajax"
    },
        {
        "uri": "freefeaturerequests\/ajax-html\/subscribers",
        "name": "freefeaturerequests.subscribers"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        console.log('laroute not initialized, can not add module routes:');
        console.log(module_routes);
    }
})();
