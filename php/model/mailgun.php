<?php 
namespace pbj\model\v1_0;

class Mailgun extends BaseModel {
  public $To;
  public $Cc;
  public $From;
  public $Subject;
  
  public $bodyPlain;
  public $bodyHtml;
  public $strippedHtml;
  public $strippedText;
  
  public $inReplyTo;
  public $Date;
  public $timestamp;
  public $messageId;
  public $contentType;
  
  public $attachmentCount;
  public $attachments;
  /*
  public $recipient;
  public $sender;
  public $subject;
  public $from;
  public $strippedSignature;
  
  public $token;
  public $signature;
  public $messageHeaders;
  public $contentIdMap;
  
  public $received;
  public $xEnvelopeFrom;
  public $DkimSignature;
  public $MimeVersion;
  public $xReceived;
  
  public $references;
  
  public $to;
  public $cc;
  
  public $xMailgunIncoming;
  */
}


?>