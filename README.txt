*** PLANCAKE PHP EMAIL PARSER ***

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
// ... or you can use the 'general purpose' method getHeader()
$emailDeliveredToHeader = $emailParser->getHeader('Delivered-To');

$emailBody = $emailParser->getPlainBody();

For more info:
https://github.com/plancake/official-library-php-email-parser

-----------------------------------------

We love to hear from you ♥. Please, send us any feedback.
Even if you don't speak English, don't worry, use your native language (we have great confidence in Google Translate :-))
http://www.plancake.com/contact

Please contact us if you need any commercial support with the installation of any component or if you would like any customization:
http://www.plancake.com/contact

Please consider contributing with bug fixes or improvements:
dan@plancake.com

Plancake homepage: http://www.plancake.com
Support: http://www.plancake.com/forums/forum/13/support-for-developers/
Subscribe to our blog: http://www.plancake.com/blog
Follow us on Twitter: http://twitter.com/plancakebakers
Follow us on Facebook: http://www.facebook.com/plancake
Donations ♥ : http://www.plancake.com/donate

"Plancake" and "Plancake Team" are trademarks of Daniele Occhipinti.
(by the way, what about using Plancake Team for team collaboration? team.plancake.com)

Brought to you by Danyuki Software Limited, a startup tech company based in London, UK.



Happy plancaking!

Daniele Occhipinti
Director and Founder of Plancake
email: dan@plancake.com
skype: dan_plan (I can speak English and Italian)
