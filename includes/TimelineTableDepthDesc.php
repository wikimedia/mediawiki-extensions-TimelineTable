<?php

/**
 * Class with constant descriptors for depth.
 */
abstract class TimelineTableDepthDesc {

	const Year = 0;
	const Month = 1;
	const Week = 2;
	const Day = 3;
	const Hour = 4;
	const Minute = 5;
	const Second = 6;

	// phpcs:ignore MediaWiki.Commenting.FunctionComment.MissingDocumentationPublic
	public static function decodeDepthDesc( $str ) {
		switch ( strtolower( $str ) ) {
			case "year":
				return self::Year;
			case "month":
				return self::Month;
			case "week":
				return self::Week;
			case "day":
				return self::Day;
			case "hour":
				return self::Hour;
			case "minute":
				return self::Minute;
			case "second":
				return self::Second;
			default:
				return null;
		}
	}
}
