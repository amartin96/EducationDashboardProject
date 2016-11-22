<?php
	require 'amartinSQL.php';
	// fetch settings from INI file
	$config = parse_ini_file("resources/config.ini");
	$hostname = $config["hostname"];
	$username = $config["username"];
	$password = $config["password"];
	$database = $config["database"];

	$dict_select = array();
	$dict_select["pass"] 	= "100 * COUNT( CASE WHEN (division = 'I' OR division = 'DISTINCTION' OR division = 'II' OR division = 'MERIT' OR division = 'III' OR division = 'CREDIT' OR division = 'IV' OR division = 'PASS') THEN 1 END ) / COUNT(*) AS 'value'";
	$dict_select["top3div"] = "100 * COUNT( CASE WHEN (division = 'I' OR division = 'DISTINCTION' OR division = 'II' OR division = 'MERIT' OR division = 'III' OR division = 'CREDIT') THEN 1 END ) / COUNT(*) AS 'value'";
	$dict_select["div1"] 	= "100 * COUNT( CASE WHEN (division = 'I' OR division = 'DISTINCTION') THEN 1 END ) / COUNT(*) AS 'value'";
	$dict_select["div2"] 	= "100 * COUNT( CASE WHEN (division = 'II' OR division = 'MERIT') THEN 1 END ) / COUNT(*) AS 'value'";
	$dict_select["div3"] 	= "100 * COUNT( CASE WHEN (division = 'III' OR division = 'CREDIT') THEN 1 END ) / COUNT(*) AS 'value'";
	$dict_select["div4"] 	= "100 * COUNT( CASE WHEN (division = 'IV' OR division = 'PASS') THEN 1 END ) / COUNT(*) AS 'value'";
	$dict_select["fail"] 	= "100 * COUNT( CASE WHEN (division = '0' OR division = 'FLD' OR division = 'FAIL') THEN 1 END ) / COUNT(*) AS 'value'";

	$dict_where = array();
	$dict_where["male"] 			= "gender = 'M'";
	$dict_where["female"] 			= "gender = 'F'";
	$dict_where["exclude-absent"] 	= "division = 'I' OR division = 'DISTINCTION' OR division = 'II' OR division = 'MERIT' OR division = 'III' OR division = 'CREDIT' OR division = 'IV' OR division = 'PASS' OR (division = '0' OR division = 'FLD' OR division = 'FAIL')";
	
	// create connection to database
	$connection = new mysqli($hostname, $username, $password, $database);
	
	// check connection
	if ($connection->connect_error)
	{
		error_log(__FILE__ . ": ERROR CONNECTING TO DATABASE");
		exit(1);
	}
	
	// send SQL query
	$query = new amartinSQL();
	$query->select( array("`hc-key`", $dict_select[$_REQUEST["data"]]) );
	$query->from( array( amartinSQL::escape($_REQUEST["year"]) ) );
	if (!empty($_REQUEST["gender"]))
		$query->where( array($dict_where[$_REQUEST["gender"]]) );
	if (!empty($_REQUEST["filter"]))
		$query->where( array($dict_where[$_REQUEST["filter"]]) );
	$query->group_by( array("`hc-key`") );
	$result = $connection->query($query->getQuery());
	// check result
	if ($result === FALSE)
	{
		error_log(__FILE__ . ": BAD QUERY: \"" . $query->getQuery() . "\" on line " . __LINE__);
		exit(1);
	}

	// format result as associative array
	$data = array();
	while ($row = $result->fetch_assoc())
		$data[] = $row;
	
	// calculate min and max
	$rangequery =
	"
		SELECT
			MIN( value ) as 'min',
			MAX( value ) as 'max'
		FROM
		(
			SELECT " . $dict_select[$_REQUEST["data"]] . "
			FROM " . amartinSQL::escape($_REQUEST["year"]) . "
			GROUP BY `hc-key`
		) count;
	";
	$result = $connection->query($rangequery);
	if ($result === FALSE)
	{
		error_log(__FILE__ . ": BAD QUERY: \"" . $rangequery . "\" on line " . __LINE__);
		exit(1);
	}
	$result = $result->fetch_assoc();
	$min = $result["min"];
	$max = $result["max"];
	
	// create object
	$pre_json = array
	(
		"min" => $min,
		"max" => $max,
		"data" => $data
	);
	
	// print data as JSON
	$json = json_encode($pre_json);
	echo $json;
?>