<?php

/**
 * @covers GoogleAnalyticsHooks
 */
class WikidoncaAnalyticsHooksTest extends MediaWikiLangTestCase {
	public function setUp() {
		parent::setUp();
		$this->setMwGlobals( 'wgWikidoncaAnalyticsAccount', '' );
	}
	/**
	 * @param $allowed
	 * @return Skin
	 */
	private function mockSkin( $allowed, $title = 'Main Page' ) {
		$skin = $this->getMockBuilder( 'SkinFallback' )
			->disableOriginalConstructor()
			->setMethods( array( 'getUser', 'getTitle' ) )
			->getMock();
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->setMethods( array( 'isAllowed' ) )
			->getMock();

		$user->expects( $this->any() )
			->method( 'isAllowed' )
			->will( $this->returnValue( $allowed ) );
		$skin
			->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( $title ) ) );

		return $skin;
	}

	/**
	 * @dataProvider provideUserPermissions
	 */
	public function testUserPermissions( $allowed, $expected ) {
		$text = '';
		WikidoncaAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( $allowed ), $text );
		$this->assertContains( $expected, $text );
	}

	public static function provideUserPermissions() {
		return array(
			array( false, 'No web analytics configured' ),
			array( true, 'Web analytics code inclusion is disabled for this user' ),
		);
	}

	public function testAccountIdSet() {
		$this->setMwGlobals( 'wgWikidoncaAnalyticsAccount', 'foobarbaz' );
		$text = '';
		WikidoncaAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertContains( 'www.google-analytics.com/analytics.js', $text );
		$this->assertContains( 'foobarbaz', $text );
		$this->setMwGlobals( 'wgWikidoncaAnalyticsAccount', '' );
		WikidoncaAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertContains( 'No web analytics configured', $text );
		$this->setMwGlobals( 'wgWikidoncaAnalyticsOtherCode', 'analytics.example.com/foo.js' );
		WikidoncaAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertContains( 'analytics.example.com/foo.js', $text );
	}

	public function testAnonymizeIp() {
		$this->setMwGlobals( 'wgWikidoncaAnalyticsAccount', 'foobarbaz' );
		$text = '';
		WikidoncaAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertContains( 'anonymizeIp', $text );
		$this->setMwGlobals( 'wgWikidoncaAnalyticsAnonymizeIP', false );
		$text = '';
		WikidoncaAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertNotContains( 'anonymizeIp', $text );
	}

	/**
	 * @dataProvider provideExcludedPages
	 */
	public function testExcludedPages( $type, $conf, $title, $include ) {
		$this->setMwGlobals( $type, array( $conf ) );
		$text = '';
		WikidoncaAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false, $title ), $text );
		if ( $include ) {
			$this->assertContains( 'No web analytics configured', $text );
		} else {
			$this->assertContains( 'Web analytics code inclusion is disabled for this page', $text );
		}
	}

	public static function provideExcludedPages() {
		return array(
			array( 'wgWikidoncaAnalyticsIgnoreSpecials', 'Preferences', 'Special:Preferences', false ),
			array( 'wgWikidoncaAnalyticsIgnoreSpecials', 'Userlogout', 'Special:Preferences', true ),
			array( 'wgWikidoncaAnalyticsIgnoreNsIDs', NS_HELP, 'Help:FooBar', false ),
			array( 'wgWikidoncaAnalyticsIgnoreNsIDs', NS_MAIN, 'Help:FooBar', true ),
			array( 'wgWikidoncaAnalyticsIgnorePages', 'Help:FooBar', 'Help:FooBar', false ),
			array( 'wgWikidoncaAnalyticsIgnorePages', 'Help:FooBar', 'Help:FooBarBaz', true ),
		);
	}
}
