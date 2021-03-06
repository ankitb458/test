<?php
/**
 * Handles the querying of the Amazon product database
 * @package now-reading
 */

/**
 * Fetches and parses XML from Amazon for the given query.
 * @param string $query Query string containing variables to search Amazon for. Valid variables: $isbn, $title, $author
 * @return array Array containing each book's information.
 */
function query_amazon( $query ) {
	global $item, $items;
	
	$options = get_option('nowReadingOptions');
	
	$using_isbn = false;
	
	parse_str($query);
	
	if ( empty($isbn) && empty($title) && empty($author) )
		return false;
	
	if ( !empty($isbn) )
		$using_isbn = true;
	
	// Our query needs different vars depending on whether or not we're searching by ISBN, so build it here.
	if ( $using_isbn ) {
		$isbn = preg_replace('#([^0-9x]+)#i', '', $isbn);
		$query = "&Power=asin%3A+$isbn";
	} else {
		$query = '&Title=' . urlencode($title);
		if ( !empty($author) )
			$query .= '&Author=' . urlencode($author);
	}
	
	$url =	'http://webservices.amazon' . $options['domain'] . '/onca/xml?Service=AWSECommerceService'
			. '&AWSAccessKeyId=0BN9NFMF20HGM4ND8RG2&Operation=ItemSearch&SearchIndex=Books&ResponseGroup=Request,Large,Images'
			. '&Version=2005-03-23&AssociateTag=' . urlencode($options['associate']).$query;
	
	// Fetch the XML using either Snoopy or cURL, depending on our options.
	if ( $options['httpLib'] == 'curl' ) {
		if ( !function_exists('curl_init') ) {
			return new WP_Error('curl-not-installed', __('cURL is not installed correctly.', NRTD));
		} else {
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Now Reading ' . NOW_READING_VERSION);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			
			if ( !empty($options['proxyHost']) ) {
				$proxy = $options['proxyHost'];
				
				if ( !empty($options['proxyPort']) ) {
					$proxy .= ":{$options['proxyPort']}";
				}
				
				curl_setopt($ch, CURLOPT_PROXY, $proxy);
			}
			
			$xmlString = curl_exec($ch);
			
			curl_close($ch);
		}
	} else {
		require_once ABSPATH . WPINC . '/class-snoopy.php';
		
		$snoopy = new snoopy;
		$snoopy->agent = 'Now Reading ' . NOW_READING_VERSION;
		
		if ( !empty($options['proxyHost']) )
			$snoopy->proxy_host = $options['proxyHost'];
		if ( !empty($options['proxyHost']) && !empty($options['proxyPort']) )
			$snoopy->proxy_port = $options['proxyPort'];
		
		$snoopy->fetch($url);
		
		$xmlString = $snoopy->results;
	}
	
	if ( empty($xmlString) ) {
		do_action('nr_search_error', $query);
		echo '
		<div id="message" class="error fade">
			<p><strong>' . __("Oops!") . '</strong></p>
			<p>' . sprintf(__("For some reason, I couldn't search for your book on amazon%s.", NRTD), $options['domain']) . '</p>
			<p>' . __("Amazon's Web Services may be down, or there may be a problem with your server configuration.") . '</p>
								
					';
					if ( $options['httpLib'] )
			echo '<p>' . __("Try changing your HTTP Library setting to <strong>cURL</strong>.", NRTD) . '</p>';
					echo '
		</div>
		';
		return false;
	}
	
	if ( $options['debugMode'] )
		robm_dump("raw XML:", htmlentities(str_replace(">", ">\n", str_replace("<", "\n<", $xmlString))));
	
	if ( !class_exists('MiniXMLDoc') )
		require_once dirname(__FILE__) . '/xml/minixml.inc.php';
	
	$xmlString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xmlString);
	$doc = new MiniXMLDoc();
	$doc->fromString($xmlString);
	
	if ( $options['debugMode'] ) {
		robm_dump("doc:", $doc);
		robm_dump("doc xml:", htmlentities($doc->toString()));
	}
	
	$items = $doc->getElementByPath('ItemSearchResponse/Items');
	if ( $items )
		$items = $items->getAllChildren('Item');
	
	if ( $options['debugMode'] )
		robm_dump("items:", $items);
	
	if ( count($items) > 0 ) {
		
		$results = array();
		
		if ( $options['debugMode'] )
			robm_dump("items:", $items);
		
		foreach ( (array) $items as $item ) {
			$author	= $item->getElementByPath('ItemAttributes/Author');
			if ( $author )
				$author	= $author->getValue();
			if ( empty($author) )
				$author = apply_filters('default_book_author', 'Unknown');
			
			$title	= $item->getElementByPath('ItemAttributes/Title');
			if ( !$title )
				break;
			$title	= $title->getValue();
			
			$asin = $item->getElement('ASIN');
			if ( !$asin )
				break;
			$asin = $asin->getValue();
			
			if ( $options['debugMode'] )
				robm_dump("book:", $author, $title, $asin);
			
			$image	= $item->getElementByPath("{$options['imageSize']}Image/URL");
			if ( $image )
				$image	= $image->getValue();
			else
				$image = get_option('siteurl') . '/wp-content/plugins/now-reading/no-image.png';
			
			$results[] = apply_filters('raw_amazon_results', compact('author', 'title', 'image', 'asin'));
		}
		
		$results = apply_filters('returned_books', $results);
	} else {
		
		return false;
		
	}
	
	return $results;
}

?>