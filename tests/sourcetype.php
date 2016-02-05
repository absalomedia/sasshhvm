<?hh
$sass = new Sass();
$sass->setIndent(true);
try {
	var_dump($sass->compileFile('tests/sass/source_type.sass'));
} catch (Exception $se) {
	echo 'Caught '.get_class($se)." in ".$se->getFile()." on line ".$se->getLine()."\nMessage: ".$se->getMessage()."\n".$se->getTraceAsString()."\n";
}
