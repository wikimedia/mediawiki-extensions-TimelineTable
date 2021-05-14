<?php

/**
 * Helper class for date operations:
 *  -calculate the number of cells between two dates depending on the depth
 *  -get first/last day of a given year
 */
class TimelineTableDateDiffHelper {

	/**
	 * Get first day in year (modify input $date)
	 *
	 * @param DateTime $date
	 * @param int|string $year
	 */
	public static function getFirstDay( $date, $year ) {
		$date->modify( "first day of january " . $year );
		$date->setTime( 0, 0, 0 );
	}

	/**
	 * Get last day in year (modify input $date)
	 *
	 * @param DateTime $date
	 * @param int|string $year
	 */
	public static function getLastDay( $date, $year ) {
		$date->modify( "last day of december " . $year );
		$date->setTime( 23, 59, 59 );
	}

	/**
	 * Calculate the number of cells between two dates (public interface)
	 *
	 * @param DateTime|null $date1
	 * @param DateTime|null $date2
	 * @param int $depth One of the TimelineTableDepthDesc::… constants
	 * @return int|null
	 */
	public static function getNumCells( $date1, $date2, $depth ) {
		if ( !is_a( $date1, "DateTime" ) || !is_a( $date2, "DateTime" ) ) {
			return null;
		}
		$startYear = intval( $date1->format( "Y" ) );
		$endYear = intval( $date2->format( "Y" ) );

		switch ( $depth ) {

			case TimelineTableDepthDesc::Year:
				$int = $date1->diff( $date2 );
				$nYears = $endYear - $startYear + 1;

				return ( $int->invert ? -1 : 1 ) * $nYears;

			case TimelineTableDepthDesc::Month:
				$m1 = intval( $date1->format( "n" ) );
				$m2 = intval( $date2->format( "n" ) );
				if ( $startYear == $endYear ) {
					$nMonths = $m2 - $m1 + 1;
				} else {
					$datecur = clone $date1;
					self::getLastDay( $datecur, $startYear );
					$nMonths = 0;

					$nMonths += intval( $datecur->format( "n" ) ) -
						$m1 + 1;
					$nMonths += 12 * ( $endYear - $startYear - 1 );
					$nMonths += intval( $date2->format( "n" ) );
				}
				$int = $date1->diff( $date2 );

				return ( $int->invert ? -1 : 1 ) * $nMonths;

			case TimelineTableDepthDesc::Week:
				if ( $startYear == $endYear ) {
					$w1 = intval( $date1->format( "W" ) );
					$w2 = intval( $date2->format( "W" ) );
					if ( $date1 <= $date2 ) {
						if ( $w1 <= $w2 ) {
							$nWeeks = $w2 - $w1 + 1;
						} else {
							// Wrap around: last day in current year is in the first
							// week of the following year
							$datecur = new DateTime( $startYear . "-12-28" );
							$nWeeks = intval( $datecur->format( "W" ) ) - $w1 + 1;
						}
					} else {
						if ( $w1 >= $w2 ) {
							$nWeeks = $w2 - $w1 + 1;
						} else {
							// Wrap around: last day in current year is in the first
							// week of the following year
							$datecur = new DateTime( $startYear . "-12-28" );
							$nWeeks = intval( $datecur->format( "W" ) ) - $w2 + 1;
						}
					}
				} else {
					$datecur = new DateTime( $startYear . "-12-28" );
					$nWeeks = 0;

					if ( $date1 < $datecur ) {
						$nWeeks += intval( $datecur->format( "W" ) ) -
							intval( $date1->format( "W" ) ) + 1;
					}

					for ( $y = $startYear + 1; $y < $endYear; $y++ ) {
						$datecur = new DateTime( $y . "-12-28" );
						$nWeeks += intval( $datecur->format( "W" ) );
					}
					$nWeeks += intval( $date2->format( "W" ) );
				}
				$int = $date1->diff( $date2 );

				return ( $int->invert ? -1 : 1 ) * $nWeeks;

			case TimelineTableDepthDesc::Day:
				$int = $date1->diff( $date2 );
				$nDays = $int->days + 1;

				return ( $int->invert ? -1 : 1 ) * $nDays;

			case TimelineTableDepthDesc::Hour:
				$int = $date1->diff( $date2 );
				$h1 = intval( $date1->format( "H" ) );
				$h2 = intval( $date2->format( "H" ) );
				$nHours = $int->days * 24 + $h2 - $h1 + 1;

				return ( $int->invert ? -1 : 1 ) * $nHours;

			case TimelineTableDepthDesc::Minute:
				$int = $date1->diff( $date2 );
				$h1 = intval( $date1->format( "H" ) );
				$h2 = intval( $date2->format( "H" ) );
				$m1 = intval( $date1->format( "i" ) );
				$m2 = intval( $date2->format( "i" ) );
				$nMinutes = $int->days * 24 * 60 + ( $h2 - $h1 ) * 60 +
							$m2 - $m1 + 1;

				return ( $int->invert ? -1 : 1 ) * $nMinutes;

			case TimelineTableDepthDesc::Second:
				$int = $date1->diff( $date2 );
				$h1 = intval( $date1->format( "H" ) );
				$h2 = intval( $date2->format( "H" ) );
				$m1 = intval( $date1->format( "i" ) );
				$m2 = intval( $date2->format( "i" ) );
				$s1 = intval( $date1->format( "s" ) );
				$s2 = intval( $date2->format( "s" ) );
				$nSeconds = $int->days * 24 * 3600 + ( $h2 - $h1 ) * 3600 +
							( $m2 - $m1 ) * 60 + $s2 - $s1 + 1;

				return ( $int->invert ? -1 : 1 ) * $nSeconds;
		}
	}
}
