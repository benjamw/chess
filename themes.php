<?php

require_once 'includes/config.inc.php';

// open up the images directory and collect the folder names
$dir = opendir('images');

while (false !== ($file = readdir($dir)))
{
	if (is_dir('images/'.$file) && (false === strpos($file, '.'))) // scanning for visible subfolders only
	{
		$dirlist[] = $file;
	}
}

closedir($dir);

// make an array for the pieces
$pieces = array(
	'king' ,
	'queen' ,
	'rook' ,
	'bishop' ,
	'knight' ,
	'pawn' ,
);

// make an array for the colors
$colors = array(
	'white' ,
	'black' ,
);

$html = '';
foreach($dirlist as $dir)
{
	$html .= '<h2>'.$dir.'</h2>';
	
	foreach($colors as $color)
	{
		foreach($pieces as $piece)
		{
			$html .= '<img src="images/'.$dir.'/'.$color.'_'.$piece.'.gif" alt="'.ucfirst($color).' '.ucfirst($piece).'" />'."\n";
		}
		
		$html .= '<br />';
	}
	
	$html .= '<hr />';
}

?><!doctype html>
<html lang="en">
<head>
<title>Theme Index</title>
<style type="text/css">
body    {font-family:sans-serif;}
a       {color:navy;}
a:hover {text-decoration:none;}
img     {border:0;display:inline;}
</style>
</head>
<body>
<h1>Index of piece themes available for use</h1>
<a href="index.php?page=prefs">Return to Preferences</a>
<hr />
<div>
<?php echo $html; ?>
</div>
</body>
</html>