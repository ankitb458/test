<?php

require_once(dirname(__FILE__).'/minixml.inc.php');

$WS_ROOT = "http://api.creativecommons.org/rest/1.5/";
$FS_ROOT = dirname(__FILE__).'/static_xml/';

function fopenEnabled() {

   // return TRUE if we can open URLs with fopen
   if (ini_get("allow_url_fopen")) {
      return TRUE;
   }

   return FALSE;

} // fopenEnabled

function retrieveFile($path) {
   // retrieve the specified path from the web services root or, 
   // if unavailable, from the local static cache

   global $WS_ROOT;
   global $FS_ROOT;

if (fopenEnabled()) {
   // try to retrieve the information from the CC web services
      $result = file_get_contents($WS_ROOT.$path);
  
      if (!($result === FALSE)) {
         return $result;
      }
} else {
   // fallback to filesystem cache
   if (!$path) {
      $path = "classes";
   }

   return file_get_contents($FS_ROOT.$path);
}

} // retrieveFile


function localIssue($answers) {
   // issue a license using a locally cached copy of the XSLT

   global $FS_ROOT;

   // switch behavior depending on PHP4 v PHP5
   if (function_exists("xslt_process")) {
      // PHP4 behavior

      $xp = xslt_create() or die("Could not create XSLT processor");
      $xslt_string = join("", file($FS_ROOT."/xslt/chooselicense.xsl"));
   
      $arg_buffer = array("/xml" => $answers, 
                          "/xslt" => $xslt_string);

      if($result = xslt_process($xp, "arg:/xml", "arg:/xslt", NULL, $arg_buffer))
      {
         // return result
         return $result;
      }   
   } else {
      // PHP 5 behavior

      $xml = new DOMDocument;
      $xml->loadXML($answers);// ('collection.xml');

      $xsl = new DOMDocument;
      $xsl->load($FS_ROOT."/xslt/chooselicense.xsl");

      // Configure the transformer
      $proc = new XSLTProcessor;
      $proc->importStyleSheet($xsl); // attach the xsl rules

      return $proc->transformToXML($xml);

   } // php 5

} // localIssue
   
function licenseClasses() {
   
   $l_classes = array();

   // retrieve the license 
   $xml = retrieveFile(""); 

   // parse the classes into a hash
   $xmlDoc = new MiniXMLDoc();
   $xmlDoc->fromString($xml);

   $root =& $xmlDoc->getRoot();
   $root =& $root->getElement('licenses');
   $licenses = $root->getAllChildren('license');

   foreach ($licenses as $l) {
      $l_classes[strval($l->attribute('id'))] = strval($l->getValue());
   }

   return $l_classes;

} // licenseClasses

function licenseQuestions($lclass) {

   $uri = 'license/'.$lclass."/"; 
   $questions = array();

   // retrieve the license 
   $xml = retrieveFile($uri); 
 
   // parse the classes into a hash
   $xmlDoc = new MiniXMLDoc();
   $xmlDoc->fromString($xml);

   $root =& $xmlDoc->getRoot();
   $root =& $root->getElement('licenseclass');
   $fields = $root->getAllChildren('field');

   foreach ($fields as $field) {
    $f_id = strval($field->attribute('id'));
    $questions[$f_id] = array();

    $el =& $field->getElement('label');
    $questions[$f_id]['label'] = strval($el->getValue());

    $el =& $field->getElement('description');
    $questions[$f_id]['description'] = strval($el->getValue());

    $el =& $field->getElement('type');
    $questions[$f_id]['type'] = strval($el->getValue());

    $questions[$f_id]['options'] = array();

    foreach ($field->getAllChildren('enum') as $enum) {
       $el =& $enum->getElement('label');
       $questions[$f_id]['options'][(string)$enum->attribute('id')] = (string)$el->getValue();
    } // for each enum

   } // foreach

   return $questions;
} // licenseQuestions

function issueLicense($lic_class, $answers) {

   global $WS_ROOT;
   $result = array();

   // do some brain-dead validation on $answers
   if (!isset($answers['jurisdiction'])) {
      $answers['jurisdiction'] = '-';
   } 

   // assemble the answers XML fragment
   $answers_xml = "<answers><license-" . $lic_class . ">";

   foreach ($answers as $field_id=>$value) {
      $answers_xml .= "<" . $field_id . ">" . $value . "</" . $field_id . ">";
   } // for each answer

   $answers_xml .= "</license-" . $lic_class . "></answers>";

   // make the web service request
   $xml = FALSE;
if (fopenEnabled()) {
      $uri = $WS_ROOT."license/" . $lic_class . "/issue?answers=" . urlencode($answers_xml);
      $xml = file_get_contents($uri);
} else {
   // check if remote retrieval failed and fall back to local if necessary
     $xml = localIssue($answers_xml);
   }

   // extract the license information
   $xmlDoc = new MiniXMLDoc();
   $xmlDoc->fromString($xml);

   $root =& $xmlDoc->getRoot();
   $root =& $root->getElement('result');

   $el =& $root->getElement('license-uri');
   $result["uri"] = strval($el->getValue());

   $el =& $root->getElement('license-name');
   $result["name"] = strval($el->getValue());

   $el =& $root->getElement('rdf');
   $result["rdf"] = $el->toString();

   // use a regexp to extract the HTML to avoid any problems with miniXml
   preg_match("/(<html>)([\s\S]*)(<\/html>)/", $xml, $matches);
   $result["html"] = $matches[2];

   return $result;
} // issueLicense
?>
