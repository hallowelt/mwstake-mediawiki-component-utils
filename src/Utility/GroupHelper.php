<?php
namespace MWStake\MediaWiki\Component\Utils\Utility;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserGroupManager;
use Wikimedia\Rdbms\IDatabase;

class GroupHelper {

	/** @var UserGroupManager */
	private $userGroupManager = null;
	/** @var array */
	private $additionalGroups = [];
	/** @var array */
	private $groupTypes = [];
	/** @var IDatabase */
	private $dbr = null;
	/** @var string[] */
	private $standardGroupsFilter = [ 'core-minimal', 'extension-minimal', 'custom' ];
	/** @var MediaWikiServices */
	protected $services;
	/** @var array */
	protected $aGroups = [];

	/**
	 * @param UserGroupManager $userGroupManager
	 * @param array $additionalGroups
	 * @param array $groupTypes
	 * @param IDatabase $dbr
	 */
	public function __construct( UserGroupManager $userGroupManager,
			$additionalGroups, $groupTypes, IDatabase $dbr ) {
		$this->userGroupManager = $userGroupManager;
		$this->additionalGroups = $additionalGroups;
		$this->groupTypes = $groupTypes;
		$this->dbr = $dbr;
	}

	/**
	 * Returns the group type for a given group
	 * @param string $group
	 * @return array
	 */
	public function getGroupType( $group ) {
		// Find groupTypes for unknown groups to filter for
		if ( !isset( $this->groupTypes[$group] ) ) {
			if ( array_key_exists( $group, $this->additionalGroups ) ) {
				// If declared by GroupManager in gm-settings.php
				$this->groupTypes[$group] = 'custom';
			} else {
				// Otherwise we assume the group was introduced by
				// an extension
				$this->groupTypes[$group] = 'extension-extended';
			}
		}
		return $this->groupTypes[$group];
	}

	/**
	 *
	 * @param array $aConf
	 * @return array
	 */
	public function getAvailableGroups( $aConf = [] ) {
		$aBlacklist = [];

		if ( isset( $aConf['blacklist'] ) ) {
			if ( !is_array( $aConf['blacklist'] ) ) {
				$aConf['blacklist'] = (array)$aConf['blacklist'];
			}
			$aBlacklist = $aConf['blacklist'];
		}

		$groupsFilter = $this->standardGroupsFilter;
		if ( isset( $aConf['filter'] ) ) {
			if ( !is_array( $aConf['filter'] ) ) {
				$aConf['filter'] = (array)$aConf['filter'];
			}
			$groupsFilter = $aConf['filter'];
		}

		$bDoReload = false;
		if ( isset( $aConf['reload'] ) ) {
			$bDoReload = $aConf['reload'];
		}
		if ( empty( $this->aGroups ) ) {
			$bDoReload = true;
		}

		if ( $bDoReload ) {
			$this->aGroups = array_merge(
				$this->userGroupManager->listAllImplicitGroups(),
				$this->userGroupManager->listAllGroups()
			);
			$this->aGroups = array_diff( $this->aGroups, $aBlacklist );
			natsort( $this->aGroups );
		}

		// Bypass if $wgGroupTypes is not set or if there is no filter.
		if ( !$this->groupTypes || !count( $this->groupTypes ) || !count( $groupsFilter ) ) {
			return $this->aGroups;
		}

		$filteredGroups = [];
		foreach ( $this->aGroups as $group ) {
			foreach ( $groupsFilter as $filter ) {
				if ( $filter == 'explicit' && !( $this->getGroupType( $group ) == 'implicit' ) ) {
					$filteredGroups[] = $group;
					continue 2;
				}
				if ( $this->getGroupType( $group ) == $filter ) {
					$filteredGroups[] = $group;
					continue 2;
				}
			}
		}
		return $filteredGroups;
	}

	/**
	 * @param string $group
	 *
	 * @return int
	 */
	public function countUsersInGroup( $group ): int {
		$res = $this->dbr->selectRow(
			'user_groups',
			'COUNT(*) AS count',
			[ 'ug_group' => $group ],
			__METHOD__
		);
		return (int)$res->count;
	}

	/**
	 * Returns an array of User being in one or all groups given
	 * @param mixed $aGroups
	 * @return array Array of User objects
	 */
	public static function getUserInGroups( $aGroups ) {
		$services = MediaWikiServices::getInstance();
		$dbr = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		if ( !is_array( $aGroups ) ) {
			$aGroups = [ $aGroups ];
		}
		$aUser = [];
		$res = $dbr->select(
			'user_groups',
			[ 'ug_user' ],
			[ 'ug_group' => $aGroups ],
			__METHOD__,
			[ 'DISTINCT' ]
			);
		if ( !$res ) {
			return $aUser;
		}
		$userFactory = $services->getUserFactory();
		foreach ( $res as $row ) {
			$aUser[] = $userFactory->newFromId( $row->ug_user );
		}
		return $aUser;
	}

}
