<?php

/**
 * TimelineTable hooks and parser
 */
class TimelineTableHooks {

	/**
	 * Register <timelinetable> hook
	 */
	public static function efTimelineTableParserInit( $parser ) {
		$parser->setHook( 'timelinetable',
			'TimelineTableHooks::efTimelineTableRender' );

		return true;
	}

	/**
	 * After tidy
	 */
	public static function efTimelineTableAfterTidy( &$parser, &$text ) {
		// find markers in $text
		// replace markers with actual output
		global $markerList;
		if ( !isset( $markerList ) ) {
			return true;
		}
		for ( $i = 0; $i < count( $markerList ); $i++ ) {
			$text = preg_replace( '/xx-marker' . $i . '-xx/',
				$markerList[$i], $text );
		}

		return true;
	}

	/**
	 * Define the html code as a marker, then change it back to text in
	 * 'efTimelineAfterTidy'. This is done to prevent the html code from being
	 * modified afterwards.
	 */
	private static function makeOutputString( $str ) {
		global $markerList;
		$makercount = count( $markerList );
		$marker = "xx-marker" . $makercount . "-xx";
		$markerList[$makercount] = $str;

		return $marker;
	}

	/**
	 * Add <pre></pre> tags around error message and return
	 */
	private static function makeErrorOutputString( $errMsg ) {
		$errMsg = "TimelineTable:<br/>" . $errMsg;
		$errMsg = Html::rawElement( 'pre', [], $errMsg );

		return self::makeOutputString( $errMsg );
	}

	/**
	 * Main function: parse input and create HTML table with events
	 */
	public static function efTimelineTableRender( $input, array $args,
		Parser $parser, PPFrame $frame = null
	) {
		if ( $frame === null ) {
			return self::makeOutputString( $input );
		}

		// Extract parameters from global variables
		global $wgTimelineTableFieldSeparator;
		global $wgTimelineTableDateSeparator;
		global $wgTimelineTableLineSeparator;
		global $wgTimelineTableAbbrMonth;
		global $wgTimelineTableShortMonthLen;

		// Format of month name
		$monthFormat = $wgTimelineTableAbbrMonth ? "M" : "F";

		// Parse tag arguments - title/caption
		if ( isset( $args['title'] ) ) {
			$title = $args['title'];
		} else {
			$title = "";
		}
		if ( isset( $args['caption'] ) ) {
			$caption = $args['caption'];
		} else {
			// Try old style name: footer
			if ( isset( $args['footer'] ) ) {
				$caption = $args['footer'];
			} else {
				$caption = "";
			}
		}

		// Use vertical table rendering?
		$flagVertical = isset( $args['vertical'] );

		// Get desired depth
		if ( isset( $args['depth'] ) ) {
			$depthStr = $args['depth'];
			// Get depth descriptor from string
			$depth = TimelineTableDepthDesc::decodeDepthDesc( $depthStr );
		} else {
			// Parse first date entry (pre v.2.0 inputs)
			$depth = TimelineTableTable::getDepthFromFirstDate( $input );
		}
		if ( $depth === null ) {
			$errStr = wfMessage( 'timelinetable-error-depth' )->escaped();

			return self::makeErrorOutputString( $errStr );
		}

		// Get default option table
		// loc: 1 (header), -1 (footer), 0 (no line)
		// format: see [http://www.php.net/manual/en/function.date.php]
		// substr: number of characters to display (only used for month with
		//         format F or M and days with format D or l)
		$hDefaultOpts = [
			TimelineTableDepthDesc::Year => [ 'loc' => 1,
				'format' => 'Y',
				'substr' => 0 ],
			TimelineTableDepthDesc::Month => [ 'loc' => 1,
				'format' => $monthFormat,
				'substr' => 0 ],
			TimelineTableDepthDesc::Week => [ 'loc' => 0,
				'format' => 'W',
				'substr' => 0 ],
			TimelineTableDepthDesc::Day => [ 'loc' => 1,
				'format' => 'j',
				'substr' => 0 ],
			TimelineTableDepthDesc::Hour => [ 'loc' => 0,
				'format' => 'H',
				'substr' => 0 ],
			TimelineTableDepthDesc::Minute => [ 'loc' => 0,
				'format' => 'i',
				'substr' => 0 ],
			TimelineTableDepthDesc::Second => [ 'loc' => 0,
				'format' => 's',
				'substr' => 0 ]
		];
		// Use weeks
		if ( isset( $args['useweeks'] ) ||
			$depth == TimelineTableDepthDesc::Week
		) {
			$hDefaultOpts[TimelineTableDepthDesc::Week]['loc'] = 1;
		}

		// Header/footer list
		$headerLines = [];
		$footerLines = [];

		if ( isset( $args['headers'] ) || isset( $args['footers'] ) ) {
			// populate the headers (hi=0) / footers (hi=1) table:
			// e.g. <timelinetable ... headers=Y/M-F-1 footers=D-l ... >
			// Y     => years in header
			// M-F-1 => months in header (format "F", substring of length 1)
			// D-l   => days in footer (format "l", keep full string)
			for ( $hi = 0; $hi < 2; $hi++ ) {
				switch ( $hi ) {
					case 0:
						$HFLines = & $headerLines;
						$argName = 'headers';
						break;
					case 1:
						$HFLines = & $footerLines;
						$argName = 'footers';
						break;
				}
				if ( isset( $args[$argName] ) ) {
					if ( strlen( trim( $args[$argName] ) ) == 0 ) {
						// Skip if no entry (e.g. headers="")
						continue;
					}
					$fields = explode( '/', trim( $args[$argName] ) );

					foreach ( $fields as $hField ) {

						$opts = explode( '-', trim( $hField ) );
						$nOpts = count( $opts );
						if ( $nOpts > 0 ) {
							switch ( strtolower( $opts[0] ) ) {
								case 'y':
									$level = TimelineTableDepthDesc::Year;
									$css = 'tl_years';
									break;
								case 'm':
									$level = TimelineTableDepthDesc::Month;
									$css = 'tl_months';
									break;
								case 'w':
									$level = TimelineTableDepthDesc::Week;
									$css = 'tl_weeks';
									break;
								case 'd':
									$level = TimelineTableDepthDesc::Day;
									$css = 'tl_days';
									break;
								case 'h':
									$level = TimelineTableDepthDesc::Hour;
									$css = 'tl_days';
									break;
								case 'i':
									$level = TimelineTableDepthDesc::Minute;
									$css = 'tl_days';
									break;
								case 's':
									$level = TimelineTableDepthDesc::Second;
									$css = 'tl_days';
									break;
								default:
									// Only y/m/w/d in headers/footers argument
									$eMsg = wfMessage( 'timelinetable-error-hf',
										$argName,
										$args[$argName] )->escaped();

									return self::makeErrorOutputString( $eMsg );
									// phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
									break;
							}
						}

						// Read format options
						if ( $level > $depth ) {
							continue;
						}
						if ( $nOpts > 1 ) {
							$format = $opts[1];
						} else {
							$format = $hDefaultOpts[$level]['format'];
						}
						if ( $nOpts > 2 ) {
							$substr = $opts[2];
						} else {
							$substr = $hDefaultOpts[$level]['substr'];
						}

						// Add header/footer to list
						array_push( $HFLines, [ 'level' => $level,
							'format' => $format,
							'substr' => $substr,
							'cssclass' => $css ] );
					}
				}
			}
		} else {
			// Old style options (pre v2.0)
			$topLevel = TimelineTableDepthDesc::Year;
			for ( $level = $topLevel; $level <= $depth; $level++ ) {

				$loc = $hDefaultOpts[$level]['loc'];

				if ( $loc != 0 ) {
					$format = $hDefaultOpts[$level]['format'];
					$substr = $hDefaultOpts[$level]['substr'];
					$css = '';
					$doContinue = false;
					switch ( $level ) {
						case TimelineTableDepthDesc::Year:
							if ( isset( $args['noyears'] ) ) {
								// Hide years
								$doContinue = true;
							}
							$css = 'tl_years';
							break;
						case TimelineTableDepthDesc::Month:
							if ( isset( $args['nomonths'] ) ) {
								// Hide months
								$doContinue = true;
							}
							if ( $depth < TimelineTableDepthDesc::Day ) {
								// Abbreviate months
								$substr = $wgTimelineTableShortMonthLen;
							}
							$css = 'tl_months';
							break;
						case TimelineTableDepthDesc::Week:
							$css = 'tl_weeks';
							break;
						case TimelineTableDepthDesc::Day:
							if ( isset( $args['daynames'] ) ) {
								// Show day names (if option passed)
								$format = 'D';
							}
							$css = 'tl_days';
							break;
						case TimelineTableDepthDesc::Hour:
							$css = 'tl_days';
							break;
						case TimelineTableDepthDesc::Minute:
							$css = 'tl_days';
							break;
						case TimelineTableDepthDesc::Second:
							$css = 'tl_days';
							break;
					}
					if ( $doContinue ) {
						continue;
					}
					if ( $loc == 1 || $loc == 2 ) {
						array_push( $headerLines, [ 'level' => $level,
							'format' => $format,
							'substr' => $substr,
							'cssclass' => $css ] );
					}
					if ( $loc == -1 || $loc == 2 ) {
						array_push( $footerLines, [ 'level' => $level,
							'format' => $format,
							'substr' => $substr,
							'cssclass' => $css ] );
					}
				}
			}
		}

		// Create table
		$table = new TimelineTableTable( $title, $caption );

		// Parse input (events)
		$errParse = $table->parse( $input, $depth );

		// Check parse output
		if ( strcmp( $errParse, "" ) != 0 ) {
			return self::makeErrorOutputString( $errParse );
		}

		// Setup headers
		for ( $hi = 0; $hi < 2; $hi++ ) {
			switch ( $hi ) {
				case 0:
					$HFLines = & $headerLines;
					break;
				case 1:
					$HFLines = & $footerLines;
					break;
			}
			foreach ( $HFLines as $hLines ) {

				$level = $hLines['level'];
				$format = $hLines['format'];
				$substr = $hLines['substr'];
				$cssClass = $hLines['cssclass'];
				$table->addHeader( $level, $depth, $cssClass, $hi == 0,
					$format, $substr );
			}
		}

		// Render table
		$timeline_str = $table->render( $parser, $depth, $flagVertical );

		// Return output
		return self::makeOutputString( $timeline_str );
	}
}
