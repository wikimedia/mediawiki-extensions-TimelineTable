<?php

/**
 * Class with constant descriptors for depth.
 */
abstract class TimelineTableDepthDesc {

	public const Year = 0;
	public const Month = 1;
	public const Week = 2;
	public const Day = 3;
	public const Hour = 4;
	public const Minute = 5;
	public const Second = 6;

	/**
	 * @param string $str
	 *
	 * @return int|null One of the self::… constants
	 */
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
