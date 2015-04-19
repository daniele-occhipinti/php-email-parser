<?php

// run this as:
// php run_tests.php

function printBarrier() { echo "\r\n\r\n\r\n"; }
function printnl($message) { echo "$message\r\n"; }

require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "PlancakeEmailParser.php");

$emails = glob(__DIR__ . DIRECTORY_SEPARATOR . "emails" . DIRECTORY_SEPARATOR . "*");

printBarrier();

foreach ($emails as $email) {
    printnl("Email $email");
    $emailParser = new PlancakeEmailParser(file_get_contents($email));
    printnl("subject: " . $emailParser->getSubject());
    printnl("body: " . $emailParser->getBody());
    printBarrier();
}
