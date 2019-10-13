<?php

class WikidoncaAnalyticsHooks {
	public static function InserisciCodice( Skin $skin, &$text = '' ) {
		global $wgWikidoncaAnalyticsAccount, $wgWikidoncaAnalyticsIPAnonimo, $wgWikidoncaAnalyticsAltroCodice,
			   $wgWikidoncaAnalyticsIgnoraNamespaceID, $wgWikidoncaAnalyticsIgnoraPagine, $wgWikidoncaAnalyticsIgnoraSpeciali;

		if ( $skin->getUser()->isAllowed( 'noanalytics' ) ) {
			$text .= "<!-- Web analytics code inclusion is disabled for this user. -->\r\n";
			return true;
		}

		if ( count( array_filter( $wgWikidoncaAnalyticsIgnoreSpecials, function ( $v ) use ( $skin ) {
				return $skin->getTitle()->isSpecial( $v );
			} ) ) > 0
			|| in_array( $skin->getTitle()->getNamespace(), $wgWikidoncaAnalyticsIgnoraNamespaceID, true )
			|| in_array( $skin->getTitle()->getPrefixedText(), $wgWikidoncaAnalyticsIgnoraPagine, true ) ) {
			$text .= "<!-- Web analytics code inclusion is disabled for this page. -->\r\n";
			return true;
		}

		$appended = false;

		if ( $wgWikidoncaAnalyticsAccount !== '' ) {
			$text .= <<<EOD
<script>
  (function(i,s,o,g,r,a,m){i['WikidoncaAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '
EOD
. $wgWikidoncaAnalyticsAccount . <<<EOD
', 'auto');

EOD
. ( $wgWikidoncaAnalyticsIPAnonimo ? "  ga('set', 'anonymizeIp', true);\r\n" : "" ) . <<<EOD
  ga('send', 'pageview');

</script>

EOD;
			$appended = true;
		}
		if ( $wgWikidoncaAnalyticsAltroCodice !== '' ) {
			$text .= $wgWikidoncaAnalyticsAltroCodice . "\r\n";
			$appended = true;
		}
		if ( !$appended ) {
			$text .= "<!-- No web analytics configured. -->\r\n";
		}
		return true;
	}

	public static function TestsList( array &$files ) {
		$directoryIterator = new RecursiveDirectoryIterator( __DIR__ . '/tests/' );
		$ourFiles = array();
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$ourFiles[] = $fileInfo->getPathname();
			}
		}

		$files = array_merge( $files, $ourFiles );
		return true;
	}
}
