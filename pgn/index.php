<?php

$pgnDir = opendir('.');
$fullFiles = array( );

while ( false !== ( $file = readdir($pgnDir) ) )
{
	if (preg_match("/\\.pgn/",$file)) // if the file is a pgn file...
	{
		// sort the files by game id
		$key = (preg_match('/game_(\\d++)/i', $file, $match)) ? $match[1] : 0;

		// collect the complete filename
		$fullFiles[$key] = $file;
	}
}

closedir($pgnDir);

// sort the array by key
ksort($fullFiles);

$numFiles = count($fullFiles);

?><!doctype html>
<html lang="en">
<head>
<title>PGN files for download</title>
<style type="text/css">
body    {font-family:sans-serif;}
a       {color:navy;}
a:hover {text-decoration:none;}
</style>
</head>
<body>
<h1>Index of PGN files to download</h1>
<a href="../?page=finished">Return to Finished Games</a>
<hr />
<div>
<?php

if ( 0 == $numFiles )
{
	echo "There are currently no completed games to download.\n";
}
else
{
	foreach ( $fullFiles AS $file )
	{
		echo "<a href=\"../watchgame.php?file=./pgn/{$file}\">Watch Game</a> &mdash; OR ";
		echo "Download <a href=\"{$file}\">{$file}</a><br />\n";
	}
}

?>

</div>
</body>
</html>