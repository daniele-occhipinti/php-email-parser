<?php

/*************************************************************************************
* ===================================================================================*
* Software by: Danyuki Software Limited                                              *
* This file is part of Plancake.                                                     *
*                                                                                    *
* Copyright 2009-2010-2011 by:     Danyuki Software Limited                          *
* Support, News, Updates at:  http://www.plancake.com                                *
* Licensed under the LGPL version 3 license.                                         *                                                       *
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
class PlancakeEmailParser {

    const PLAINTEXT = 1;
    const HTML = 2;
    public $hasAttachments = -1;
    private $boundary = ''; //Unique Boundary for multipart emails
    private $attachments = array(); //Attachments Array
    /**
     *
     * @var boolean
     */    
    private $isImapExtensionAvailable = false;
    
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

        $this->extractHeadersAndRawBody();
        $this->detectAttachments();
        if (function_exists('imap_open')) {
            $this->isImapExtensionAvailable = true;
        }
    }
    public function countAttachments() //Returns Attachments Count
    {
      return count($this->attachments); //counts number of Attachments
    }
	
	/*
	  Returns Attachment array:
	
	   Array([0] => Array ( [file_encoding] => base64
                               [file_type] => audio/caf 
							   [file_d_type] => attachment 
							   [file_name] => example_vmr_09102012182307.3gp 
							   [file_content] => Y2FmZgA) Array(...))
	*/
	
	public function getAttachmentArrayJ() //Attachment array in JSON format
	{
	  return json_encode($this->attachments);
	}
	
	/*
	  Returns Attachment array:
	
	   [{"file_type":"text\/plain","file_d_type":"attachment","file_name":"text.txt","file_encoding":"base64","file_content":"dGVTVGluZyA="},
	   {...}]
	*/
	
	public function getAttachmentArray() //Attachment in PHP array 
	{
	  return $this->attachments;
	}
	
    public function detectAttachments() //Attachments Detection and fills up array.
    {
      $contentTypeRegex = '/^Content-Type: ?multipart\/mixed\;/i'; 
      $lines = preg_split("/(\r?\n|\r)/", $this->emailRawContent);
      $boundary_0 = 0;
      $boundary_1 ='';
      $line_0 = 0;
      $line_1 = '';
      $line_2 = '';
      $line_3 = '';
      $line_4 = 0;
      $line_5 = 0;
      $line_6 = '';
      $line_7 = '';
      $tmp = array();
      foreach ($lines as $line)
        {
           //echo $line.'<br/>';
           
            if(preg_match($contentTypeRegex, $line, $matches, PREG_OFFSET_CAPTURE))
            {
              $this->hasAttachments = 1;
              $boundary_0 = 1;
            }
            
            if($boundary_0 == 1 )
            {
              if(strpos($line, 'boundary=')!==false)
              {
                $pos = strpos($line, 'boundary=');
                $boundary_1 = substr($line,$pos+9);
                $boundary_1 = preg_replace("/\"/","",$boundary_1);
                //echo 'boundary: '.$boundary_1.' <br/>';
                $this->boundary = $boundary_1;
                $boundary_0=0;
                continue;
              }
            }
           
        
          
           if($boundary_1 != '')           
           {
            
            //preg_match('/($boundary_1)/', $line, $matches, PREG_OFFSET_CAPTURE);
            
            if(strpos($line,'--'.$boundary_1)!==false  )
            {
             //print_r($matches);
             //echo $line; 
              if($line_0 == 5)
              {
               $tmp['file_content'] = $line_6; 
               array_push($this->attachments,$tmp);
               $tmp = array();
              }
              
              $line_0 = 1;
              $line_1 = $line_2 = $line_3 = $line_4 = 0;
              continue;
            }
           }
          
          if($line_7 == 1)
          {
          if(strpos($line,'filename=')!==false)
          {
            $line_1 = substr($line,strpos($line,'filename=')+9);
            $tmp['file_name'] = preg_replace("/\"/","",$line_1); 
          }
          $line_7 = 0;
          } 
          
          if($line_0 != 0)
          {
            switch($line_0)
            {
              case 1:
               $line_1 = $line; 
               //echo $line_1.'<br/>';
               if(strpos($line,'Content-Type:')!==false )
               {
                 $line_1 = substr($line,strpos($line,'Content-Type:')+13);
                 $line_1 = explode(";",$line_1);
                 if(strpos(trim($line_1[0]),'multipart/alternative')===false){ 
                    $tmp['file_type'] = trim($line_1[0]);
                    //echo trim($line_1[0]);
                     $line_0 = 2;
                 }//cancels the 'multipart/alternative' test
               } 
               else  if(strpos($line,'Content-Disposition:')!==false)
               {
                 $line_1 = substr($line,strpos($line,'Content-Disposition:')+20); 
                 $line_1 = explode(";",$line_1); 
                 if(trim($line_1[1])=='') //if there is a space between content-disposition and filename
                   $line_7=1;
                   $tmp['file_d_type'] = trim($line_1[0]); //echo trim($line_2[0]);
                   $line_1 = substr($line_1[1],strpos($line_1[1],'filename=')+9);
                   $tmp['file_name'] = preg_replace("/\"/","",$line_1); 
                   $line_0 = 2;
               }
               else  if(strpos($line,'Content-Transfer-Encoding:')!==false)
               {
                 $line_1 = substr($line,strpos($line,'Content-Transfer-Encoding:')+26);
                  if(strpos(trim($line_1),'quoted-printable')===false){ 
                     $tmp['file_encoding'] = trim($line_1); //echo trim($line_2);
                     $line_0 =2;
                   } //Cancels the 'quoted-printable' test
               }   
               break;

             case 2:
               $line_2 = $line; //echo $line_2.'<br/>';
               if(strpos($line,'Content-Disposition:')!==false)
               {
                 $line_2 = substr($line,strpos($line,'Content-Disposition:')+20);
                 $line_2 = explode(";",$line_2); 
                    if(trim($line_2[1])=='') //if there is a space between content-disposition and filename
                       $line_7=1; 
                   $tmp['file_d_type'] = trim($line_2[0]); //echo trim($line_2[0]);
                   $line_2 = substr($line_2[1],strpos($line_2[1],'filename=')+9);
                   $tmp['file_name'] = preg_replace("/\"/","",$line_2); //echo $line_2;
                   $line_0 = 3;
                
               }
               else  if(strpos($line,'Content-Transfer-Encoding:')!==false)
               {
                 $line_2 = substr($line,strpos($line,'Content-Transfer-Encoding:')+26);
                  if(strpos(trim($line_2),'quoted-printable')===false){ 
                     $tmp['file_encoding'] = trim($line_2); //echo  trim($line_2);
                     $line_0 = 3;
                   } //Cancels the 'quoted-printable' test
               }
               else  if(strpos($line,'Content-Type:')!==false )
               {
                 $line_2 = substr($line,strpos($line,'Content-Type:')+13);
                 $line_2 = explode(";",$line_2);
                 if(strpos(trim($line_2[0]),'multipart/alternative')===false){ 
                    $tmp['file_type'] = trim($line_2[0]);
                    //echo trim($line_1[0]);
                     $line_0 = 3;
                 }//cancels the 'multipart/alternative' test
               } 
               break;

             case 3:
               $line_3 = $line; //echo $line_3.'<br/>';
               if(strpos($line,'Content-Transfer-Encoding:')!==false)
               {
                 $line_3 = substr($line,strpos($line,'Content-Transfer-Encoding:')+26);
                 if(strpos(trim($line_3),'quoted-printable')===false){ 
                    $tmp['file_encoding'] = trim($line_3); //echo  trim($line_3);
                 
                     $line_4 = 1;
                     $line_0 = 4;
                   } //Cancels the 'quoted-printable' test 
               }
               else if(strpos($line,'Content-Disposition:')!==false)
               {
                 $line_3 = substr($line,strpos($line,'Content-Disposition:')+20);
                 $line_3 = explode(";",$line_3); 
                     if(trim($line_3[1])=='') //if there is a space between content-disposition and filename
                       $line_7=1; 
                    $tmp['file_d_type'] = trim($line_3[0]); //echo trim($line_3[0]);
                    $line_3 = substr($line_3[1],strpos($line_3[1],'filename=')+9);
                    $tmp['file_name'] = preg_replace("/\"/","",$line_3); //echo $line_3;
                    $line_4 = 1;
                    $line_0 = 4;
                 
               } 
               else  if(strpos($line,'Content-Type:')!==false )
               {
                 $line_3 = substr($line,strpos($line,'Content-Type:')+13);
                 $line_3 = explode(";",$line_3);
                 if(strpos(trim($line_3[0]),'multipart/alternative')===false){ 
                    $tmp['file_type'] = trim($line_3[0]);
                    //echo trim($line_1[0]);
                     $line_4 = 1;
                     $line_0 = 4;
                 }//cancels the 'multipart/alternative' test
               } 
               break;
             case 4:
              if(trim($line)=='')
              {
                $line_0 = 5;
                $line_6 = '';
              }
              break;
              
             case 5:
              $line_6.=$line;
             break;
             
            }

          }

        } //end of for each

       //print_r($this->attachments); 
        
    }
    private function extractHeadersAndRawBody()
    {
        $lines = preg_split("/(\r?\n|\r)/", $this->emailRawContent);
        //echo $this->emailRawContent.'<hr/>';
        $currentHeader = '';

        $i = 0;
        foreach ($lines as $line)
        {
            //echo $line.'<br/>';
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
                if ($currentHeader) { // to prevent notice from empty lines
                    $this->rawFields[$currentHeader] .= substr($line, 1);
                }
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
        
        $ret = '';
        
        if ($this->isImapExtensionAvailable) {
            foreach (imap_mime_header_decode($this->rawFields['subject']) as $h) { // subject can span into several lines
                $charset = ($h->charset == 'default') ? 'US-ASCII' : $h->charset;
                $ret .=  iconv($charset, "UTF-8//TRANSLIT", $h->text);
            }
        } else {
            $ret = utf8_encode(iconv_mime_decode($this->rawFields['subject']));
        }
        
        return $ret;
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
     */
    public function getFrom()
    {
       
    $email = trim($this->rawFields['from']);

     if(substr($email, -1) == '>'){
        $fromarr = explode("<",$email);
        $mailarr1 = explode(">",$fromarr[1]);
        $email = $mailarr1[0];
     }

    return $email;

    }
    /**
     *
     * @return array
     */
    public function getAllRaw()
    {
      print_r($this->rawFields);
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
     * return string - UTF8 encoded
     * 
     * Example of an email body
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
    public function getBody($returnType=self::PLAINTEXT)
    {
        $body = '';
        $detectedContentType = false;
        $contentTransferEncoding = null;
        $charset = 'ASCII';
        $waitingForContentStart = true;

        if ($returnType == self::HTML)
            $contentTypeRegex = '/^Content-Type: ?text\/html/i';
        else
            $contentTypeRegex = '/^Content-Type: ?text\/plain/i';
        
        // there could be more than one boundary
        preg_match_all('!boundary=(.*)$!mi', $this->emailRawContent, $matches);
        $boundaries = $matches[1];
        // sometimes boundaries are delimited by quotes - we want to remove them
        foreach($boundaries as $i => $v) {
            $boundaries[$i] = str_replace(array("'", '"'), '', $v);
        }
        
        foreach ($this->rawBodyLines as $line) {
            if (!$detectedContentType) {
                
                if (preg_match($contentTypeRegex, $line, $matches)) {
                    $detectedContentType = true;
                }
                
                if(preg_match('/charset=(.*)/i', $line, $matches)) {
                    $charset = strtoupper(trim($matches[1], '"')); 
                }       
                
            } else if ($detectedContentType && $waitingForContentStart) {
                
                if(preg_match('/charset=(.*)/i', $line, $matches)) {
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

        if (!$detectedContentType)
        {
            // if here, we missed the text/plain content-type (probably it was
            // in the header), thus we assume the whole body is what we are after
            $body = implode("\n", $this->rawBodyLines);
        }

        // removing trailing new lines
        $body = preg_replace('/((\r?\n)*)$/', '', $body);

        if ($contentTransferEncoding == 'base64')
            $body = base64_decode($body);
        else if ($contentTransferEncoding == 'quoted-printable')
            $body = quoted_printable_decode($body);        
        
        if($charset != 'UTF-8') {
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
     * 
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