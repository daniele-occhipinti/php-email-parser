<?php

/*************************************************************************************
* ===================================================================================*
* Software by: Danyuki Software Limited                                              *
* This file is part of Plancake.                                                     *
*                                                                                    *
* Copyright 2009-2010-2011 by:     Danyuki Software Limited                          *
* Support, News, Updates at:  http://www.plancake.com                                *
* Licensed under the LGPL version 3 license.                                         *
* Danyuki Software Limited is registered in England and Wales (Company No. 07554549) *
**************************************************************************************
* Plancake is distributed in the hope that it will be useful,                        *
* but WITHOUT ANY WARRANTY; without even the implied warranty of                     *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                      *
* GNU Lesser General Public License v3.0 for more details.                           *
*                                                                                    *
* You should have received a copy of the GNU Lesser General Public License           *
* along with this program.  If not, see <http://www.gnu.org/licenses/>.              *
*                                                                                    *
**************************************************************************************
*
* Valuable contributions by:
* - Chris
*
* **************************************************************************************/

/**
 * Extracts the headers and the body of an email
 * Obviously it can't extract the bcc header because it doesn't appear in the content
 * of the email.
 *
 * N.B.: if you deal with non-English languages, we recommend you install the IMAP PHP extension:
 * the Plancake PHP Email Parser will detect it and used it automatically for better results.
 *
 * For more info, check:
 * https://github.com/plancake/official-library-php-email-parser
 *
 * @author dan
 */
class PlancakeEmailParser
{
    const PLAINTEXT = 1;
    const HTML = 2;

    /**
     * @var bool
     */
    protected $isImapExtensionAvailable = false;

    /**
     * @var string
     */
    protected $emailRawContent;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var array
     */
    protected $rawFields;

    /**
     * @var string[]
     */
    protected $rawBodyLines;

    /**
     * Headers that should always replace a previous header of the same type,
     * rather than be combined into an array.
     *
     * @var array
     */
    protected $singleHeaders = array(
        "orig-date",
        "sender",
        "reply-to",
        "to",
        "cc",
        "bcc",
        "message-id",
        "in-reply-to",
        "subject",
    );

    /**
     * @param string $emailRawContent
     * @param bool $debug
     */
    public function __construct($emailRawContent, $debug = false)
    {
        $this->emailRawContent = $emailRawContent;
        $this->debug = $debug;

        $this->extractHeadersAndRawBody();

        if (function_exists('imap_open')) {
            $this->isImapExtensionAvailable = true;
        }
    }

    /**
     * @param string $message
     */
    protected function debug($message)
    {
        if ($this->debug === true) {
            var_dump($message);
        }
    }

    protected function extractHeadersAndRawBody()
    {
        $lines = preg_split("/(\r?\n|\r)/", $this->emailRawContent);

        $currentHeader = '';

        $i = 0;
        foreach ($lines as $line) {
            if (self::isNewLine($line)) {
                // end of headers
                $this->rawBodyLines = array_slice($lines, $i);
                break;
            }

            if ($this->isLineStartingWithPrintableChar($line)) {
                // start of new header
                $result = preg_match('/([^:]+): ?(.*)$/', $line, $matches);
                if (!$result) {
                    $i++;
                    continue;
                }
                $newHeader = strtolower($matches[1]);
                $value = $matches[2];
                if (isset($this->rawFields[$newHeader]) && !is_array($newHeader)) {
                    if (is_array($this->rawFields[$newHeader])) {
                        $this->rawFields[$newHeader][] = $value;
                    } else {
                        $this->rawFields[$newHeader] = array($this->rawFields[$newHeader], $value);
                    }
                } else {
                    $this->rawFields[$newHeader] = $value;
                }
                $currentHeader = $newHeader;
            } else {
                // more lines related to the current header
                if ($currentHeader) { // to prevent notice from empty lines
                    $withoutIndent = preg_replace("/^\s+/", "", $line);
                    if (is_array($this->rawFields[$currentHeader])) {
                        $this->rawFields[$currentHeader][count($this->rawFields[$currentHeader]) - 1] .= $withoutIndent;
                    } else {
                        $this->rawFields[$currentHeader] .= $withoutIndent;
                    }
                }
            }
            $i++;
        }
    }

    /**
     * @return array the parsed headers as associative array
     */
    public function getHeaders()
    {
        return $this->rawFields;
    }

    /**
     * @return string (in UTF-8 format)
     */
    public function getSubject()
    {
        if (!isset($this->rawFields['subject'])) {
            return null;
        }

        $ret = '';

        if ($this->isImapExtensionAvailable) {
            foreach (imap_mime_header_decode($this->rawFields['subject']) as $h) { // subject can span into several lines
                $charset = ($h->charset == 'default') ? 'US-ASCII' : $h->charset;
                $ret .= iconv($charset, "UTF-8//TRANSLIT", $h->text);
            }
        } else {
            $ret = utf8_encode(iconv_mime_decode($this->rawFields['subject']));
        }

        return $ret;
    }

    /**
     * @param string $userField
     * @return array
     */
    public function tokeniseUserField($userField)
    {
        $userField = trim($userField);
        $userFieldChars = self::strSplitUnicode($userField);
        $charCount = count($userFieldChars);
        $return = array();
        $curName = "";
        $curEmail = "";
        $startChars = array('"' => '"', "<" => ">");
        $startChar = null;
        for ($x = 0; $x < $charCount; $x++) {
            $this->debug("start iteration");
            $this->debug(implode("", array_slice($userFieldChars, $x)));
            if (strlen($curName) === 0 && isset($startChars[$userFieldChars[$x]])) {
                // If we haven't started processing a name yet, and the name starts with one
                // of the denoted "start characters", make a note of which character was used
                // to start the name, then move onto the next character.
                $this->debug("start mark {$userFieldChars[$x]}");
                $startChar = $startChars[$userFieldChars[$x]];
            } elseif (strlen($curName) === 0 || $startChar !== null) {
                $this->debug("start name");
                $y = $x;
                while (true) {
                    $curName .= $userFieldChars[$y];
                    $y++;
                    if ($y >= $charCount) {
                        break;
                    }
                    if ($startChar !== null) {
                        // If $startChar is set, it means we need to keep going until
                        // we find the matching end character. The point of this is to
                        // not break on things like commas and spaces, because the name
                        // has probably been quoted so that we don't break on commas
                        // and spaces.
                        if ($userFieldChars[$y] === $startChar) {
                            $y++;
                            break;
                        }
                    } else {
                        if ($userFieldChars[$y] === " " ||
                            $userFieldChars[$y] === ",") {
                            // If we hit a space, it means we're processing a name, and
                            // we're about to move onto the actual email address. If we
                            // hit a comma, it means we're processing an email address,
                            // and we're about to move onto the next entry in the list.
                            break;
                        }
                    }
                }
                $x = $y;
                $startChar = null;
            } elseif (strlen($curName) &&
                $userFieldChars[$x] === " " &&
                $userFieldChars[$x + 1] === "<") {
                // We just had a name delimieted by quotes, and now we're about to move
                // onto the actual email address.
                $this->debug("found opening bracket after a space");
                $x++;
            } elseif (strlen($curName) &&
                $userFieldChars[$x] === "<") {
                // We just had a name not delimieted by quotes, and now we're about to
                // move onto the actual email address.
                $this->debug("found opening bracket");
            } else {
                // We had a name before the actual email address. We're now grabbing the
                // actual email address.
                $this->debug("start email");
                $y = $x;
                while ($userFieldChars[$y] !== ">") {
                    $curEmail .= $userFieldChars[$y];
                    $y++;
                }
                $x = $y;
                $return[] = array(
                    "name" => $curName,
                    "email" => $curEmail,
                );
                $curName = "";
                $this->debug($return);
            }
            if (strlen($curName)) {
                $this->debug(implode("", array_slice($userFieldChars, $x)));
                if ($x >= $charCount || $userFieldChars[$x] === ",") {
                    // We had a "name" that was actually an email address with no name.
                    $this->debug("we have an email address with no name");
                    $return[] = array(
                        "name" => "",
                        "email" => $curName,
                    );
                    $curEmail = $curName;
                    $curName = "";
                }
            }
            if (strlen($curEmail)) {
                // We've found and saved an email address. This must signal the end
                // of an item in the list. Reset, and move to the start of the next
                // item (or the end of the list)
                $this->debug("we have an email, reset");
                $curEmail = "";
                $this->debug(implode("", array_slice($userFieldChars, $x)));
                while ($x < $charCount && $userFieldChars[$x] !== ",") {
                    $x++;
                }
                $this->debug(implode("", array_slice($userFieldChars, $x)));
                while ($x < $charCount && $userFieldChars[$x] === " ") {
                    $x++;
                }
                $x++;
            }
        }
        $this->debug(implode("", array_slice($userFieldChars, $x)));
        return $return;
    }

    /**
     * @return array
     * @throws Exception If no to header was found in the raw message
     */
    public function getTo()
    {
        if (!isset($this->rawFields['to'])) {
            throw new Exception("Couldn't find the recipients of the email");
        }
        return $this->tokeniseUserField($this->rawFields['to']);
    }

    /**
     * @return array
     */
    public function getCc()
    {
        if (!isset($this->rawFields['cc'])) {
            return array();
        }

        return $this->tokeniseUserField($this->rawFields['cc']);
    }

    /**
     * @return array
     */
    public function getFrom()
    {
        if (!isset($this->rawFields['from'])) {
            return array();
        }

        return $this->tokeniseUserField(implode(", ", $this->rawFields['from']));
    }

    /**
     * return string - UTF8 encoded
     *
     * Example of an email body
     *
     *  --0016e65b5ec22721580487cb20fd
     *  Content-Type: text/plain; charset=ISO-8859-1
     *
     *  Hi all. I am new to Android development.
     *  Please help me.
     *
     *  --
     *  My signature
     *
     *  email: myemail@gmail.com
     *  web: http://www.example.com
     *
     *  --0016e65b5ec22721580487cb20fd
     *  Content-Type: text/html; charset=ISO-8859-1
     */
    public function getBody($returnType = self::PLAINTEXT)
    {
        $body = '';
        $detectedContentType = false;
        $contentTransferEncoding = null;
        $charset = 'ASCII';
        $waitingForContentStart = true;

        if ($returnType == self::HTML) {
            $contentTypeRegex = '/^Content-Type: ?text\/html/i';
        } else {
            $contentTypeRegex = '/^Content-Type: ?text\/plain/i';
        }

        // there could be more than one boundary
        preg_match_all('!boundary=(.*)$!mi', $this->emailRawContent, $matches);
        $boundaries = $matches[1];
        // sometimes boundaries are delimited by quotes - we want to remove them
        foreach ($boundaries as $i => $v) {
            $boundaries[$i] = str_replace(array("'", '"'), '', $v);
        }

        foreach ($this->rawBodyLines as $line) {
            if (!$detectedContentType) {
                if (preg_match($contentTypeRegex, $line, $matches)) {
                    $detectedContentType = true;
                }

                if (preg_match('/charset=(.*)/i', $line, $matches)) {
                    $charset = strtoupper(trim($matches[1], '"'));
                }
            } else if ($detectedContentType && $waitingForContentStart) {
                if (preg_match('/charset=(.*)/i', $line, $matches)) {
                    $charset = strtoupper(trim($matches[1], '"'));
                }

                if ($contentTransferEncoding == null && preg_match('/^Content-Transfer-Encoding: ?(.*)/i', $line, $matches)) {
                    $contentTransferEncoding = $matches[1];
                }

                if (self::isNewLine($line)) {
                    $waitingForContentStart = false;
                }
            } else {  // ($detectedContentType && !$waitingForContentStart)
                // collecting the actual content until we find the delimiter
                // if the delimited is AAAAA, the line will be --AAAAA  - that's why we use substr
                if (is_array($boundaries)) {
                    if (in_array(substr($line, 2), $boundaries)) {  // found the delimiter
                        break;
                    }
                }
                $body .= $line . "\n";
            }
        }

        if (!$detectedContentType) {
            // if here, we missed the text/plain content-type (probably it was
            // in the header), thus we assume the whole body is what we are after
            $body = implode("\n", $this->rawBodyLines);
        }

        // removing trailing new lines
        $body = preg_replace('/((\r?\n)*)$/', '', $body);

        if ($contentTransferEncoding == 'base64') {
            $body = base64_decode($body);
        } else if ($contentTransferEncoding == 'quoted-printable') {
            $body = quoted_printable_decode($body);
        }

        if ($charset != 'UTF-8') {
            // FORMAT=FLOWED, despite being popular in emails, it is not
            // supported by iconv
            $charset = str_replace("FORMAT=FLOWED", "", $charset);

            $bodyCopy = $body;
            $body = iconv($charset, 'UTF-8//TRANSLIT', $body);

            if ($body === FALSE) { // iconv returns FALSE on failure
                $body = utf8_encode($bodyCopy);
            }
        }

        return $body;
    }

    /**
     * @return string - UTF8 encoded
     */
    public function getPlainBody()
    {
        return $this->getBody(self::PLAINTEXT);
    }

    /**
     * return string - UTF8 encoded
     */
    public function getHTMLBody()
    {
        return $this->getBody(self::HTML);
    }

    /**
     * @param string $headerName the header we want to retrieve
     * @return array|string|null the value(s) of the header
     */
    public function getHeader($headerName)
    {
        $headerName = strtolower($headerName);

        if (isset($this->rawFields[$headerName])) {
            return $this->rawFields[$headerName];
        }
        return null;
    }

    /**
     * @param string $line
     * @return bool
     */
    public static function isNewLine($line)
    {
        $line = str_replace("\r", '', $line);
        $line = str_replace("\n", '', $line);

        return (strlen($line) === 0);
    }

    /**
     * @param string $line
     * @return bool
     */
    private function isLineStartingWithPrintableChar($line)
    {
        return preg_match('/^[A-Za-z]/', $line);
    }

    /**
     * @param string $string
     * @return string[]
     */
    protected static function strSplitUnicode($string)
    {
        $return = array();
        $len = mb_strlen($string, "UTF-8");
        for ($i = 0; $i < $len; $i++) {
            $return[] = mb_substr($string, $i, 1, "UTF-8");
        }
        return $return;
    }
}
