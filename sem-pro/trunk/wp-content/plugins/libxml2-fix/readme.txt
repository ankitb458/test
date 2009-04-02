=== LibXML2 Fix ===
Contributors: josephscott
Tags: xml-rpc
Stable tag: 0.1

== Description ==
Work around for some versions of libxml2 2.7.x that strip out brackets when parsing XML.  This plugin fixes XML-RPC requests that are mangled because of this problem.  The real fix for this (making the use of this plugin unnecessary) is to use PHP 5.2.9+ with libxml2 2.7.3+.  

For more information about this problem see <http://core.trac.wordpress.org/ticket/7771>.
