<?php

namespace MWStake\MediaWiki\Component\Utils\Tests;

use MWStake\MediaWiki\Component\Utils\Utility\GroupHelper;
use PHPUnit\Framework\TestCase;

class GroupHelperTest extends TestCase {

	/** @var string[] */
	public static $implicitGroups = [ '*', 'user', 'autoconfirmed' ];
	/**
	 * As per includes/user/User.php::getAllGroups, implicit groups are not
	 * included.
	 * @var string[]
	 */
	public static $allGroups = [ 'sysop', 'bureaucrat', 'bot', 'editor', 'my-reviewer' ];
	/** @var array */
	public static $additionalGroups = [ 'my-reviewer' => [] ];
	/** @var string[] */
	public static $groupTypes = [
		'*'                => 'implicit',
		'user'             => 'implicit',
		'autoconfirmed'    => 'implicit',
		'sysop'            => 'core-minimal',
		'bureaucrat'       => 'core-extended',
		'bot'              => 'core-extended',
		'interface-admin'  => 'core-extended',
		'suppress'         => 'core-extended',
		'autoreview'       => 'extension-extended',
		'editor'           => 'extension-minimal',
		'review'           => 'extension-extended',
		'reviewer'         => 'extension-minimal',
		'smwcurator'       => 'extension-extended',
		'smweditor'        => 'extension-extended',
		'smwadministrator' => 'extension-extended',
		'widgeteditor'     => 'extension-extended'
	];

	/**
	 * @covers \MWStake\MediaWiki\Component\Utils\Utility\GroupHelper::getAvailableGroups
	 * @dataProvider provideGetAvailableGroupsTestData
	 */
	public function testGetAvailableGroups( $implicitGroups, $allGroups, $additionalGroups,
						$groupTypes, $filter, $expectedAvailableGroups ) {
		$groupManager = $this->createMock( \MediaWiki\User\UserGroupManager::class );
		$groupManager->method( 'listAllImplicitGroups' )->willReturn( $implicitGroups );
		$groupManager->method( 'listAllGroups' )->willReturn( $allGroups );

		$dbr = $this->createMock( \Wikimedia\Rdbms\IDatabase::class );

		$groupHelper = new GroupHelper( $groupManager, $additionalGroups, $groupTypes, $dbr );
		$availableGroups = $groupHelper->getAvailableGroups( $filter );

		$this->assertEquals( $expectedAvailableGroups, $availableGroups );
	}

	public function provideGetAvailableGroupsTestData() {
		return [
			'filter-for-core-minimal-only' => [
				self::$implicitGroups,
				self::$allGroups,
				self::$additionalGroups,
				self::$groupTypes,
				[ 'filter' => [ 'core-minimal' ] ],
				[ 'sysop' ]
			],
			'filter-for-implicit-only' => [
				self::$implicitGroups,
				self::$allGroups,
				self::$additionalGroups,
				self::$groupTypes,
				[ 'filter' => [ 'implicit' ] ],
				[ '*', 'autoconfirmed', 'user' ]
			],
			'filter-for-explicit-only' => [
				self::$implicitGroups,
				self::$allGroups,
				self::$additionalGroups,
				self::$groupTypes,
				[ 'filter' => [ 'explicit' ] ],
				[ 'bot', 'bureaucrat', 'editor', 'my-reviewer', 'sysop' ]
			],
			'filter-for-usable-groups' => [
				self::$implicitGroups,
				self::$allGroups,
				self::$additionalGroups,
				self::$groupTypes,
				[ 'notset' => [] ],
				[ 'editor', 'my-reviewer', 'sysop' ]
			],
			'filter-for-custom-groups' => [
				self::$implicitGroups,
				self::$allGroups,
				self::$additionalGroups,
				self::$groupTypes,
				[ 'filter' => [ 'custom' ] ],
				[ 'my-reviewer' ]
			],
			'wgGroupType not set' => [
				self::$implicitGroups,
				self::$allGroups,
				self::$additionalGroups,
				null,
				[ 'filter' => [ 'custom' ] ],
				[ '*', 'user', 'autoconfirmed', 'sysop', 'bureaucrat', 'bot', 'editor', 'my-reviewer' ]
			]
		];
	}
}
