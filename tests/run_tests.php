<?php

// run this as:
// php run_tests.php

require_once("../PlancakeEmailParser.php");

$emails = glob('./emails/*');

echo "\r\n\r\n\r\n";

foreach($emails as $email) {
	echo "Email $email \r\n";
	$emailParser = new PlancakeEmailParser(file_get_contents($email));
	echo "subject: " . $emailParser->getSubject() . "\r\n";
	echo "body: " . $emailParser->getBody() . "\r\n";
	echo "\r\n\r\n\r\n";
}
