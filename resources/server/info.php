<?php
/*
 * This is an example application for my friend learning PHP. My goal was to show him some of the better ways to do
 * things in PHP. In a real world example, one's application would have more functionality (such as submitting data or
 * searching for specific rows) and in that case more Object Orientated Programming concepts should be employed. As this
 * was a very simple application, OOP was not implemented.
 */

// Define constants for the response codes we're going to send back to the client. Generally, if you're using a number
// in a statement you'll want to define it as a constant. A good way to tell if you need to make a number a constant is
// to isolate the statement where you use the number and pretend you've never seen that bit of code before; would someone
// who has never seen that snippet of code before instantly be able to tell why you chose that number?
define('SUCCESS', 200);
define('BAD_REQUEST', 400);
define('NO_DATA', 404);
define('SERVER_ERROR', 500);

// We use the super global $_GET here as our parameters are in the URL (info.php?page=1&count=15).
$pageNum = isset($_GET['page']) ? $_GET['page'] : 1; // Check to make sure a page number was passed. If not, then assume the first page.
// If a count was given, then use that. Else, use 15. This also prevents too many results from being returned.
$count = isset($_GET['count']) && $_GET['count'] < 50 ? $_GET['count'] : 15;
$response = []; // Brackets are a shorthand way of creating an array.

try { // Try-Catch blocks allow one to handle exceptions if they occur. If an exception occurs, execute the catch block.

    // It is typically recommended to use PHP Data Objects (PDO) for any communication with a database. PDO helps make
    // it safe to take user input and place it inside a query. Below, we open up the connection to the database and then
    // pass it a few constants to control its behavior; those are described below:
    // * ATTR_PERSISTENT: Since this is false, this means that the connections to the database are closed once the
    //                    script ends.
    // * ATTR_ERRMODE: This sets the error mode for PDO. It is currently set to throw an exception if the connection
    //                 or an operation fails for any reason.
    // * ATTR_EMULATE_PREPARES: With this set to false, this prevents PHP from parsing the statement and rather allowing
    //                          MySQL to do so instead.
	$db = new PDO(
	'mysql:host=address;dbname=dbName;',
	'admin',
	'password',
	array(PDO::ATTR_PERSISTENT => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false));

    $data = [];
	$start = (($pageNum - 1) * $count); // Represents where to start when fetching data.

    // Here we prepare the query. The parameters with the colons in front of them will be replaced when we bind
    // the parameters to a variable. We use this query to make sure we actually have results to return to the client.
    $countQ = $db->prepare("SELECT COUNT(results.id) AS count FROM (SELECT * FROM info ORDER BY id LIMIT :start, :numResults) AS results");

    // PDO prepared statements are great for preventing SQL injection; the query is processed separately from the
    // parameters. This means that the parameters will be escaped prior to being used in the statement, thus preventing
    // SQL injection. I like this StackOverflow answer explaining why: http://stackoverflow.com/a/60496
    $countQ->bindParam(':start', $start, PDO::PARAM_INT);
    $countQ->bindParam(':numResults', $count, PDO::PARAM_INT);
    $countQ->execute();

    if ($countQ->fetchColumn() > 0) { // Actually fetch the result of $countQ and compare it against 0.
        // Since the last query gives zero if we're not given results, it's safe to assume that $start and $count are safe.
        $query = $db->query("SELECT * FROM info ORDER BY id LIMIT $start, $count");
        $rows = $query->fetchAll(PDO::FETCH_ASSOC); // Retrieve the data itself.
        foreach($rows as $row) { // Now we restructure the data to pass to the respond function.
            $data[$row['id']] = [
                'creditor' => $row['creditor'],
                'date' => date('d/m/Y', strtotime($row['date'])),
                'score' => $row['fako'],
                'state' => $row['state'],
                'income' => number_format($row['income']),
                'approval' => (boolean)$row['approval'], // Need to cast this to a boolean.
                'credit_line' => number_format($row['creditline']),
                'interest' => $row['interest'],
                'comments' => $row['comments']
            ];
        }
        $rows = null;
        respond(SUCCESS, $data);
    } else {
        respond(NO_DATA, "Sorry, no data found!");
    }
    $countQ = null;
    $db = null; // Best practice to nullify your connection to the database. This essentially closes the connection.
} catch (PDOException $e) { // If a PDOException occurs, run the following code.
	// Below are a few common error codes. You could toss these in constants but for error codes it's assumed that
	// you'll reference the documentation.
	switch($e->getCode()) {
	    case 1045:
	        respond(SERVER_ERROR, "Sorry, a database error occurred. Please contact your system administrator.");
	        break;
	    case 42000:
	        respond(BAD_REQUEST, "Sorry, there seems to be an issue with your request. Please refresh and try again.");
	        break;
	    default:
	        respond();
	        break;
	}
}

// A small function to handle the response sent back to the client. This function will format the data and echo it.
// Default parameters are included for simplicity's sake.
function respond($status = SERVER_ERROR, $data = "Sorry, an unknown error has occurred. Please contact your system administrator.") {
	$response = [
        'status' => (int)$status
    ];
	
	if (is_string($data)) {
		$response['message'] = $data;
	} else {
		$response['data'] = [
            'length' => (int)count($data),
            'rows' => $data
        ];
	}
	
	// We will return our data in JSON, which stands for JavaScript Object Notation. JSON is a nice, human-readable way
	// to send data back and forth. It's easy for Javascript to parse as well (hence the name!) By using echo here, we
    // send the response back to the client, who is waiting for it to arrive.
	echo json_encode($response);
}

?>