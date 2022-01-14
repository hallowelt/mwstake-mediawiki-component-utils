<?php

namespace MWStake\MediaWiki\Component\Utils;

use MediaWiki\MediaWikiServices;

/**
 * UtilityFactory class for MWStake components
 */
class UtilityFactory {

	/**
	 *
	 * @var MediaWikiServices
	 */
	protected $services = null;

	/**
	 * @param MediaWikiServices $services
	 */
	public function __construct( MediaWikiServices $services ) {
		$this->services = $services;
	}

	/**
	 * @return \MWStake\MediaWiki\Component\Utils\Utility\GroupHelper
	 */
	public function getGroupHelper() {
		$groupManager = $this->services->getUserGroupManager();
		$config = $this->services->getMainConfig();
		$additionalGroups = $config->get( 'AdditionalGroups' );
		$groupTypes = $config->get( 'GroupTypes' );
		$dbr = $this->services->getDBLoadBalancer()->getConnection( DB_REPLICA );

		return new Utility\GroupHelper( $groupManager, $additionalGroups, $groupTypes, $dbr );
	}
}
