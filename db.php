<?php
$serverName		= "Bilal\\SQLEXPRESS";
	$connectionOptions	= array(
		"Database"	=> "ipmhDB",
		"Uid"		=> "",
		"PWD"		=> ""
	);

	$conn = sqlsrv_connect($serverName, $connectionOptions);

	if ($conn === false) 
    {  
		die(print_r(sqlsrv_errors(), true));
	}

function fmtDate($d) {
    return ($d instanceof DateTime) ? $d->format('Y-m-d') : ($d ?? 'N/A');
}

function sqlErr() {
    return print_r(sqlsrv_errors(), true);
}
?>
