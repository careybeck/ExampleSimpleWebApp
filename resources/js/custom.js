// Custom.js is where a lot of the communication between the server and the client happens. This file is responsible for
// sending data to the server and showing the returned data to the client.

var page = 1; // This keeps track of what page we're on.
var count = 15; // This keeps track of how many results we want returned at a time.

// This is the first bit of jQuery for you. The $() selects an element to do something with (in this case, it's the
// whole document. The .ready() just says to run the function we defined inside of it whenever the item (in this case
// the document/page) is finished loading.
$(document).ready(function() {
    getData(page, count); // Function defined toward the bottom fo the file.

    if (page < 2) { // If the page number is 1, then disable the navigation links.
        // The disabled class is a part of Twitter Bootstrap; it deactivates a navigation item in index.html.
        // Note how we're selecting the item with the ID prevLiItem. A '#' tells the selector to look at the ID for
        // prevLiItem; a '.' tells the selector to look at the class for prevLiItem.
        $('#prevLiItem').addClass('disabled');
    }
});

// Notice that we're selecting the element with the ID prevPage, and we execute the function inside whenever we click
// on the element prevPage (which is a navigation button).
$('#prevPage').on('click', function() {
    // Control pagination; does much of the same as the above but adjusts the page number and downloads the new data
    // from the server.
    page--;
    getData(page, count);

    if (page < 2) {
        $('#prevLiItem').addClass('disabled');
        page = 1;
    }
});

// Does a similar thing as above, but for the next page button instead.
$('#nextPage').on('click', function() {
    page++;
    $('#prevLiItem').removeClass('disabled');
    getData(page, count);
});

// Get data is the communication layer between index.html and info.php.
function getData(pageNum, resultCount) {
    // Here we do the Asynchronous Javascript And XML (AJAX) request. Note the HTTP verb we're using is GET. This maps
    // to the R of the CRUD (Create, Retrieve, Update, and Delete) operations. Typically it's nice to stick to the
    // following notation:
    // * Create = POST
    // * Retrieve = GET
    // * Update = UPDATE
    // * Delete = DELETE
    // This is one of the foundations of representational state transfer, or REST
    // (http://en.wikipedia.org/wiki/Representational_state_transfer). The next parameter is the URL; this is the
    // address we want to target with the AJAX request. Note that we pass the page number and number of results we want
    // through the URL. Typically, this is okay unless you want to hide this information (like a username and password).
    // Finally, we specify a timeout in case the request hangs for any reason.
    var request = $.ajax({
        type: "GET",
        url: 'resources/server/info.php?page=' + pageNum + '&count=' + resultCount,
        timeout: 800 // 800 milliseconds.
    });

    // If the AJAX call receives a response successfully, this function will be called. The data parameter in the
    // function is the data we received from the server.
    request.done(function(data) {
        var response = JSON.parse(data); // Parse the JSON we receive from the server. See info.php.
        if (response.status === 200) { // We defined 200 to be a successful status in info.php.
            var table = $('#myTable'); // Find the table in the document.
            table.find('tr:gt(0)').remove(); // Remove all the rows in the document but the header row.
            $.each(response.data.rows, function(i, row) { // For each data row, add it as a row to our table and format it.
                table.find('tbody:last').append('<tr>' +
                '<td><em>' + row.creditor + '</em></td>' +
                '<td>' + row.date + '</td>' +
                '<td>' + row.score + '</td>' +
                '<td>' + row.state + '</td>' +
                '<td>$' + row.income + '</td>' +
                (row.approval ? '<td class="success">Approved' : '<td class="danger">Not Approved') + '</td>' +
                '<td>$' + row.credit_line + '</td>' +
                '<td>' + row.interest + '%</td>' +
                '<td>' + row.comments + '</td></tr>');
            });

            $('#pageNum').text(pageNum); // Update the page number.
            
            if (response.data.length < count) { // Check to see if we need to disable/enable the next button.
                $('#nextLiItem').addClass('disabled');
            } else {
                $('#nextLiItem').removeClass('disabled');
            }
        // If the status wasn't 200, then something went wrong. Bring up the modal in index.html and let the user know.
        } else {
            $('#modalMessage').text(response.message);
            $('#myModal').modal('show');
        }
    });

    // If the AJAX call doesn't receive a proper response (like if the file info.php didn't exist), we bring up the
    // modal with a message alerting the user that something is wrong.
    request.fail(function() {
        $('#modalMessage').text("Sorry, unable to contact the server! Please contact your administrator.");
        $('#myModal').modal('show');
    });
}