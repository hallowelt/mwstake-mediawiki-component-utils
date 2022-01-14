<?php

use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;

return [
	'MWStakeCommonUtilsFactory' => function ( MediaWikiServices $services ) {
		return new UtilityFactory(
			$services
		);
	}
];
