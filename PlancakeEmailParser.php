<?php

/*************************************************************************************
* ===================================================================================*
* Software by: Danyuki Software Limited                                              *
* This file is part of Plancake.                                                     *
*                                                                                    *
* Copyright 2009-2010-2011 by:     Danyuki Software Limited                          *
* Support, News, Updates at:  http://www.plancake.com                                *
* Licensed under the LGPL version 3 license.                                         *
*                                                                                    *
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
**************************************************************************************/

/**
 * Extracts the headers and the body of an email
 * Obviously it can't extract the bcc header because it doesn't appear in the content
 * of the email.
 *
 * @author dan
 */
class PlancakeEmailParser {
   
    private $htmlEncoding;
    private $plainEncoding;

    /**
     *
     * @var string
     */
    private $emailRawContent;

    /**
     *
     * @var associative array
     */
    protected $rawFields;

    /**
     *
     * @var array of string (each element is a line)
     */
    protected $rawBodyLines;

    /**
     *
     * @param string $emailRawContent
     */
    public function  __construct($emailRawContent) {
        $this->emailRawContent = $emailRawContent;
        $this->htmlEncoding = null;
        $this->plainEncoding = null;

        $this->extractHeadersAndRawBody();
    }

    private function extractHeadersAndRawBody()
    {
        $lines = preg_split("/(\r?\n|\r)/", $this->emailRawContent);

        $currentHeader = '';

        $i = 0;
        foreach ($lines as $line)
        {
            if(self::isNewLine($line))
            {
                // end of headers
                $this->rawBodyLines = array_slice($lines, $i);
                break;
            }
           
            if ($this->isLineStartingWithPrintableChar($line)) // start of new header
            {
                preg_match('/([^:]+): ?(.*)$/', $line, $matches);
                $newHeader = strtolower($matches[1]);
                $value = $matches[2];
                $this->rawFields[$newHeader] = $value;
                $currentHeader = $newHeader;
            }
            else // more lines related to the current header
            {
                $this->rawFields[$currentHeader] .= substr($line, 1);
            }
            $i++;
        }
    }

    /**
     *
     * @return string (in UTF-8 format)
     * @throws Exception if a subject header is not found
     */
    public function getSubject()
    {
        if (!isset($this->rawFields['subject']))
        {
            throw new Exception("Couldn't find the subject of the email");
        }
        return utf8_encode(iconv_mime_decode($this->rawFields['subject']));
    }

    /**
     *
     * @return array
     */
    public function getCc()
    {
        if (!isset($this->rawFields['cc']))
        {
            return array();
        }

        return explode(',', $this->rawFields['cc']);
    }

    /**
     *
     * @return array
     * @throws Exception if a to header is not found or if there are no recipient
     */
    public function getTo()
    {
        if ( (!isset($this->rawFields['to'])) || (!count($this->rawFields['to'])))
        {
            throw new Exception("Couldn't find the recipients of the email");
        }
        return explode(',', $this->rawFields['to']);
    }

    /**
     * @return string - UTF8 encoded
     *
        --0016e65b5ec22721580487cb20fd
        Content-Type: text/plain; charset=ISO-8859-1

        Hi all. I am new to Android development.
        Please help me.

        --
        My signature

        email: myemail@gmail.com
        web: http://www.example.com

        --0016e65b5ec22721580487cb20fd
        Content-Type: text/html; charset=ISO-8859-1
     */
    public function getPlainBody()
    {
        $previousLine = '';
        $plainBody = '';
        $delimiter = '';
        $detectedContentType = false;
        $waitingForContentStart = true;

        foreach ($this->rawBodyLines as $line) {
            if (!$detectedContentType) {
                if (preg_match('/^Content-Type: ?text\/plain/', $line, $matches)) {
                    $detectedContentType = true;
                    $delimiter = $previousLine;
                }
            } else if ($detectedContentType && $waitingForContentStart) {
                if(preg_match('/^Content-Transfer-Encoding: (.*)$/', $line, $matches))
                {
                    $this->plainEncoding = $matches[1];
                }
                else if (self::isNewLine($line)) {
                    $waitingForContentStart = false;
                }
            } else {  // ($detectedContentType && !$waitingForContentStart)
                // collecting the actual content until we find the delimiter
                if ($line == $delimiter) {  // found the delimiter
                    break;
                }
                $plainBody .= $line . "\n";
            }

            $previousLine = $line;
        }

        if (!$detectedContentType)
        {
            // if here, we missed the text/plain content-type (probably it was)
            // in the header, thus we assume the whole body is plain text
            $plainBody = implode("\n", $this->rawBodyLines);
        }

        // removing trailing new lines
        $plainBody = preg_replace('/((\r?\n)*)$/', '', $plainBody);

        switch($this->plainEncoding)
        {
            case 'base64':
                return utf8_encode(base64_decode($plainBody));      
            default:
                return utf8_encode(quoted_printable_decode($plainBody));
        }
    }

    /**
     * return string - UTF8 encoded
     */
    public function getHTMLBody()
    {
        $previousLine = '';
        $htmlBody = '';
        $delimiter = '';
        $detectedContentType = false;
        $waitingForContentStart = true;

        foreach ($this->rawBodyLines as $line) {
            if (!$detectedContentType) {
                if (preg_match('/^Content-Type: ?text\/html/', $line, $matches)) {
                    $detectedContentType = true;
                    $delimiter = $previousLine;
                }
            } else if ($detectedContentType && $waitingForContentStart) {
                if(preg_match('/^Content-Transfer-Encoding: (.*)$/', $line, $matches))
                {
                    $this->htmlEncoding = $matches[1];
                }
                else if (self::isNewLine($line)) {
                    $waitingForContentStart = false;
                }
            } else {  // ($detectedContentType && !$waitingForContentStart)
                // collecting the actual content until we find the delimiter
                if ($line == $delimiter) {  // found the delimiter
                    break;
                }
                $htmlBody .= $line . "\n";
            }

            $previousLine = $line;
        }

        switch($this->htmlEncoding)
        {
            case 'base64':
                return utf8_encode(base64_decode($htmlBody));
            case 'quoted-printable':      
                return utf8_encode(quoted_printable_decode($htmlBody));
            default:
                return utf8_encode($htmlBody);
        }
    }

    /**
     * N.B.: if the header doesn't exist an empty string is returned
     *
     * @param string $headerName - the header we want to retrieve
     * @return string - the value of the header
     */
    public function getHeader($headerName)
    {
        $headerName = strtolower($headerName);

        if (isset($this->rawFields[$headerName]))
        {
            return $this->rawFields[$headerName];
        }
        return '';
    }

    /**
     *
     * @param string $line
     * @return boolean
     */
    public static function isNewLine($line)
    {
        $line = str_replace("\r", '', $line);
        $line = str_replace("\n", '', $line);

        return (strlen($line) === 0);
    }

    /**
     *
     * @param string $line
     * @return boolean
     */
    private function isLineStartingWithPrintableChar($line)
    {
        return preg_match('/^[A-Za-z]/', $line);
    }
}
?>
