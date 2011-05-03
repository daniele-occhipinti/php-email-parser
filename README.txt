*** PLANCAKE PHP EMAIL PARSER ***

This library allows you to easily parse an email given its content (headers + body).

Usage example:



$emailPath = "/var/mail/spool/dan/new/12323344323234234234";
$emailParser = new PlancakeEmailParser(file_get_contents($emailPath));

$emailTo = $emailParser->getTo();
$emailSubject = $emailParser->getSubject();
$emailCc = $emailParser->getCc();
$emailDeliveredToHeader = $emailParser->getHeader('Delivered-To');
$emailBody = $emailParser->getPlainBody();


----------------------------------------------------------

Please contact us if you need any commercial support with the installation of any component or if you would like any customization:
http://www.plancake.com/contact

Please consider contributing any bug fix fixes or improvements:
http://www.plancake.com/contact

Plancake homepage: http://www.plancake.com
Support: http://www.plancake.com/forums/forum/13/support-for-developers/
Donations: http://www.plancake.com/donate
