Project Status
--------
This project is no longer maintained.

It might still be used (at your own risk) for small projects, especially where you know the exact format of the emails you are going to parse. However, this piece of software is surely not equipped for parsing the wide variety of email formats out there.

There are a number of known issues which can be found in the [Issues section](https://github.com/daniele-occhipinti/php-email-parser/issues) on this page. There are a number of suggested code changes in the [Pull requests sections](https://github.com/daniele-occhipinti/php-email-parser/pulls) on this page.

For better alternatives, [this Stackoverflow page may be of help](https://stackoverflow.com/questions/4721410/best-way-to-handle-email-parsing-decoding-in-php).



Documentation
--------

N.B.: if you deal with non-English languages, we recommend you install the IMAP PHP extension:
the Plancake PHP Email Parser will detect it and used it automatically for better results.

This library allows you to easily parse an email given its content (headers + body).

Usage example:


$emailPath = "/var/mail/spool/dan/new/12323344323234234234";
$emailParser = new PlancakeEmailParser(file_get_contents($emailPath));

// You can use some predefined methods to retrieve headers...
$emailTo = $emailParser->getTo();
$emailSubject = $emailParser->getSubject();
$emailCc = $emailParser->getCc();
$emailFrom = $emailParser->getFrom();
// ... or you can use the 'general purpose' method getHeader()
$emailDeliveredToHeader = $emailParser->getHeader('Delivered-To');

$emailBody = $emailParser->getPlainBody();
