<?php

use MediaWiki\Html\Html;

/**
 * Main class handling input parsing and rendering of the table
 */
class TimelineTableTable {

	/**
	 * @var string Table title
	 */
	public $tableTitle = "";

	/**
	 * @var string Table caption
	 */
	public $tableCaption = "";

	/**
	 * @var TimelineTableEvent[][] Table content: list of event lists (one list per table row)
	 */
	public $tableLines = [];

	/**
	 * @var TimelineTableEvent[][] Table header lines
	 */
	public $tableHeaderLines = [];

	/**
	 * @var TimelineTableEvent[][] Table footer lines
	 */
	public $tableFooterLines = [];

	/**
	 * @var DateTime|null Earliest start date
	 */
	public $startDate;

	/**
	 * @var DateTime|null Latest end date
	 */
	public $endDate;

	/**
	 * @param string $title
	 * @param string $caption
	 */
	public function __construct( $title, $caption ) {
		$this->tableTitle = $title;
		$this->tableCaption = $caption;
	}

	/**
	 * Render HTML table
	 *
	 * @param Parser $parser
	 * @param int $depth
	 * @param false $flagVert
	 * @return string
	 */
	public function render( $parser, $depth, $flagVert = false ) {
		if ( $flagVert ) {
			return $this->renderVertical( $parser, $depth );
		} else {
			return $this->renderHorizontal( $parser, $depth );
		}
	}

	/**
	 * Render table vertically
	 *
	 * @param Parser $parser
	 * @param int $depth
	 * @return string
	 */
	public function renderVertical( $parser, $depth ) {
		// Total number of cells
		$nTotalCells = TimelineTableDateDiffHelper::getNumCells(
			$this->startDate, $this->endDate, $depth );

		// Build helper arrays for transposition
		$locTableHeader = [];
		$locTableFooter = [];
		$locTableEntry  = [];
		$idxMapHeader = [];
		$idxMapFooter = [];
		$idxMapEntry = [];
		if ( count( $this->tableHeaderLines ) > 0 ) {
			$locTableHeader = array_fill( 0, count( $this->tableHeaderLines ),
										  null );
			$this->fillVertTable( $this->tableHeaderLines, $nTotalCells,
								  $locTableHeader, $depth );
			$idxMapHeader = array_fill( 0, count( $this->tableHeaderLines ),
									0 );
		}
		if ( count( $this->tableFooterLines ) > 0 ) {
			$locTableFooter = array_fill( 0, count( $this->tableFooterLines ),
										  null );
			$this->fillVertTable( $this->tableFooterLines, $nTotalCells,
								  $locTableFooter, $depth );
			$idxMapFooter = array_fill( 0, count( $this->tableFooterLines ),
										0 );
		}
		if ( count( $this->tableLines ) > 0 ) {
			$locTableEntry = array_fill( 0, count( $this->tableLines ), null );
			$this->fillVertTable( $this->tableLines, $nTotalCells,
								  $locTableEntry, $depth );
			$idxMapEntry = array_fill( 0, count( $this->tableLines ), 0 );
		}

		// Start the table
		$ts = Html::openElement( 'table', [ 'class' => 'tl_table' ] );

		for ( $i = 0; $i < $nTotalCells; $i++ ) {
			$ts .= Html::openElement( 'tr' );

			if ( $i == 0 ) {
				if ( !empty( $this->tableTitle ) ) {
					$ts .= Html::element( 'th',
										  [ 'rowspan' => $nTotalCells,
												 'class' => 'tl_title' ],
										  $this->tableTitle );
				}
			}

			$ts .= $this->renderVertRow( $this->tableHeaderLines,
										 $locTableHeader, $i, $idxMapHeader,
										 $parser, $depth, 'th' );
			$ts .= $this->renderVertRow( $this->tableLines,
										 $locTableEntry, $i, $idxMapEntry,
										 $parser, $depth, 'td' );
			$ts .= $this->renderVertRow( $this->tableFooterLines,
										 $locTableFooter, $i, $idxMapFooter,
										 $parser, $depth, 'th' );

			if ( $i == 0 ) {
				if ( !empty( $this->tableCaption ) ) {
					$ts .= Html::element( 'td', [ 'rowspan' => $nTotalCells,
														'class' => 'tl_foot' ],
										  $this->tableCaption );
				}
			}

			$ts .= Html::closeElement( 'tr' );
		}

		// Finish table
		$ts .= Html::closeElement( 'table' );

		return $ts;
	}

	/**
	 * @param Parser $parser
	 * @param int $depth
	 * @return string
	 */
	public function renderHorizontal( $parser, $depth ) {
		// Total number of cells
		$nTotalCells = TimelineTableDateDiffHelper::getNumCells(
			$this->startDate, $this->endDate, $depth );

		// Start the table
		$ts = Html::openElement( 'table', [ 'class' => 'tl_table' ] );

		// Header: title line + headers
		if ( strlen( $this->tableTitle ) > 0 ||
			count( $this->tableHeaderLines )
		) {

			$ts .= Html::openElement( 'thead', [ 'class' => 'tl_header' ] );
			$ts .= $this->renderTitleCaption( $this->tableTitle, $nTotalCells,
				'tl_title', 'th' );
			$ts .= $this->renderHeaderFooter( $this->tableHeaderLines,
				'tl_title', $depth, $parser,
				'th' );
			$ts .= Html::closeElement( 'thead' );
		}
		// Footer: caption line + footers
		if ( strlen( $this->tableCaption ) > 0 ||
			count( $this->tableFooterLines )
		) {

			// Header: title line + headers
			$ts .= Html::openElement( 'tfoot', [ 'class' => 'tl_footer' ] );
			$ts .= $this->renderHeaderFooter( $this->tableFooterLines,
				'tl_foot', $depth, $parser,
				'th' );
			$ts .= $this->renderTitleCaption( $this->tableCaption, $nTotalCells,
				'tl_foot', 'td' );
			$ts .= Html::closeElement( 'tfoot' );
		}

		// Body: Events
		$ts .= Html::openElement( 'tbody', [ 'class' => 'tl_body' ] );
		foreach ( $this->tableLines as $hLine ) {
			$ts .= Html::openElement( 'tr' );
			foreach ( $hLine as $hEvent ) {
				if ( $hEvent->isValid() ) {
					$ts .= $hEvent->render( $parser, $depth );
				} else {
					$errMsg = $hEvent->getErrorMsg();
					$ts .= Html::element( 'td', null, $errMsg );
					break;
				}
			}
			$ts .= Html::closeElement( 'tr' );
		}
		$ts .= Html::closeElement( 'tbody' );

		// Finish table
		$ts .= Html::closeElement( 'table' );

		return $ts;
	}

	/**
	 * Render title/caption line
	 *
	 * @param string $HFText
	 * @param int $nTotalCells
	 * @param string $CSSclass
	 * @param string $type
	 * @return string
	 */
	private function renderTitleCaption( $HFText, $nTotalCells, $CSSclass,
		$type
	) {
		$ts = "";
		if ( !empty( $HFText ) ) {

			$ts .= Html::openElement( 'tr' );
			$ts .= Html::element( $type, [ 'colspan' => $nTotalCells,
					'class' => $CSSclass ],
				$HFText );
			$ts .= Html::closeElement( 'tr' );
		}

		return $ts;
	}

	/**
	 * Render header/footer sections
	 *
	 * @param TimelineTableEvent[][] $tableHFLines
	 * @param string $CSSclass
	 * @param int $depth
	 * @param Parser $parser
	 * @param string $HTMLtype
	 * @return string
	 */
	private function renderHeaderFooter( $tableHFLines, $CSSclass, $depth,
		$parser, $HTMLtype ) {
		$ts = "";

		if ( count( $tableHFLines ) > 0 ) {
			// Header/footer cells
			foreach ( $tableHFLines as $hLine ) {
				$ts .= Html::openElement( 'tr' );
				foreach ( $hLine as $hEvent ) {
					if ( $hEvent->isValid() ) {
						$ts .= $hEvent->render( $parser, $depth );
					} else {
						$errMsg = $hEvent->getErrorMsg();
						$ts .= Html::element( $HTMLtype, null, $errMsg );
						break;
					}
				}
				$ts .= Html::closeElement( 'tr' );
			}
		}

		return $ts;
	}

	/**
	 * Create helper table for vertical table
	 *
	 * @param TimelineTableEvent[][] $tableLines
	 * @param int $nTotalCells
	 * @param bool[][] &$outLocTable
	 * @param int $depth
	 */
	private function fillVertTable( $tableLines, $nTotalCells,
									&$outLocTable, $depth ) {
		for ( $hi = 0; $hi < count( $tableLines ); $hi++ ) {
			$outLocTable[ $hi ] = array_fill( 0, $nTotalCells, false );

			$idxStart = 0;
			for ( $ei = 0; $ei < count( $tableLines[ $hi ] ); $ei++ ) {
				$event = $tableLines[ $hi ][ $ei ];
				$nCells = $event->getNumCells( $depth );
				$outLocTable[ $hi ][ $idxStart ] = true;
				$idxStart += $nCells;
			}
		}
	}

	/**
	 * Render single row of vertical table
	 *
	 * @param TimelineTableEvent[][] $tableLines
	 * @param bool[][] $locTable
	 * @param int $rowIdx
	 * @param int[] &$idxMap
	 * @param Parser $parser
	 * @param int $depth
	 * @param string $HTMLtype
	 * @return string
	 */
	private function renderVertRow( $tableLines, $locTable, $rowIdx, &$idxMap,
									$parser, $depth, $HTMLtype ) {
		$ts = '';
		for ( $hi = 0; $hi < count( $tableLines ); $hi++ ) {
			if ( $locTable[ $hi ][ $rowIdx ] ) {
				$hEvent = $tableLines[ $hi ][ $idxMap[ $hi ] ];
				if ( $hEvent->isValid() ) {
					$ts .= $hEvent->render( $parser, $depth, true );
				} else {
					$errMsg = $hEvent->getErrorMsg();
					$ts .= Html::element( $HTMLtype, null, $errMsg );
					break;
				}
				$idxMap[ $hi ]++;
			}
		}
		return $ts;
	}

	/**
	 * Parse timelinetable input
	 *
	 * @param string $input
	 * @param int $depth
	 * @return string
	 */
	public function parse( $input, $depth ) {
		// Extract parameters from global variables
		global $wgTimelineTableFieldSeparator;
		global $wgTimelineTableLineSeparator;
		global $wgTimelineTableEventSeparator;

		// Get lines
		$lines = explode( $wgTimelineTableLineSeparator, trim( $input ) );

		// Get lists of events in each line
		$flagFirstEvent = true;
		$k = 0;
		foreach ( $lines as $val ) {

			$eventList = explode( $wgTimelineTableEventSeparator,
				trim( $val ) );
			$flagFirstEventLine = true;
			$this->tableLines[$k] = [];

			// Loop over events in the current line
			foreach ( $eventList as $eventStr ) {

				$event = new TimelineTableEvent();
				// Parse event
				if ( !$event->parse( $eventStr,
					$wgTimelineTableFieldSeparator )
				) {
					return $event->getErrorMsg();
				}
				$eventStartDate = $event->getStartDate();
				$eventEndDate = $event->getEndDate();

				// Update table first/last event if necessary
				if ( $flagFirstEvent || $eventStartDate < $this->startDate ) {
					$this->startDate = $eventStartDate;
				}
				if ( $flagFirstEvent || $eventEndDate > $this->endDate ) {
					$this->endDate = $eventEndDate;
				}

				// Create free-time block between successive events
				if ( !$flagFirstEventLine ) {
					$nCellsFreeTime = TimelineTableDateDiffHelper::getNumCells(
						$prevEndDate, $eventStartDate, $depth );
					// Remove two cells (exclusive range)
					$nCellsFreeTime -= 2;
					if ( $nCellsFreeTime < 0 ) {
						// If events overlap or are not in increasing order,
						// return an error
						return wfMessage( 'timelinetable-error-overlap',
							$val )->escaped();
					}
					if ( $nCellsFreeTime > 0 ) {
						$ftEvent = new TimelineTableEvent();
						$ftEvent->createEventBlock( $nCellsFreeTime, '', '',
							'tl_freetime' );
						array_push( $this->tableLines[$k], $ftEvent );
					}
				}

				// Add event to list
				array_push( $this->tableLines[$k], $event );

				// Prepare next entry
				$flagFirstEvent = false;
				$flagFirstEventLine = false;
				$prevEndDate = $eventEndDate;
			}
			$k++;
		}

		// Add leading/trailing freetime blocks
		for ( $k = 0; $k < count( $this->tableLines ); $k++ ) {

			$firstEvent = $this->tableLines[$k][0];
			$nEvents = count( $this->tableLines[$k] );
			$lastEvent = $this->tableLines[$k][$nEvents - 1];
			$firstDate = $firstEvent->getStartDate();
			$lastDate = $lastEvent->getEndDate();

			if ( $firstDate > $this->startDate ) {
				// Leading freetime block
				$nCellsFreeTime = TimelineTableDateDiffHelper::getNumCells(
					$this->startDate, $firstDate, $depth );
				// Remove one cell (exclusive range)
				$nCellsFreeTime--;
				if ( $nCellsFreeTime < 0 ) {
					// Something went wrong: there is a date earlier than the
					// overall start date, return an error
					return wfMessage( 'timelinetable-error-free', $k )->escaped();
				}
				if ( $nCellsFreeTime > 0 ) {
					$ftEvent = new TimelineTableEvent();
					$ftEvent->createEventBlock( $nCellsFreeTime, '', '',
						'tl_freetime' );
					array_unshift( $this->tableLines[$k], $ftEvent );
				}
			}

			if ( $lastDate < $this->endDate ) {
				// Trailing freetime block
				$nCellsFreeTime = TimelineTableDateDiffHelper::getNumCells(
					$lastDate, $this->endDate, $depth );
				// Remove one cell (exclusive range)
				$nCellsFreeTime--;
				if ( $nCellsFreeTime < 0 ) {
					// Something went wrong: there is a date later than the
					// overall end date, return an error
					return wfMessage( 'timelinetable-error-free', $k )->escaped();
				}
				if ( $nCellsFreeTime > 0 ) {
					$ftEvent = new TimelineTableEvent();
					$ftEvent->createEventBlock( $nCellsFreeTime, '', '',
						'tl_freetime' );
					array_push( $this->tableLines[$k], $ftEvent );
				}
			}
		}

		// Return an empty string on success (no error message)
		return "";
	}

	/**
	 * Parse first date entry and guess depth (must be in format: YYYY-MM-DD,
	 * where MM and DD may be omitted).  Returns a TimelineTableDepthDesc
	 * constant or null if first date could not be parsed.
	 *
	 * This is for backward compatibility (pre 2.0) when depth is not passed as
	 * an input argument.
	 *
	 * @param string $input
	 * @return int|null
	 */
	public static function getDepthFromFirstDate( $input ) {
		// Extract parameters from global variables
		global $wgTimelineTableFieldSeparator;
		global $wgTimelineTableLineSeparator;
		global $wgTimelineTableEventSeparator;
		global $wgTimelineTableDateSeparator;

		// Get events
		$lines = explode( $wgTimelineTableLineSeparator, trim( $input ) );

		// Get lists of events in each line
		$flagFirstEvent = true;
		$k = 0;
		foreach ( $lines as $val ) {

			$eventList = explode( $wgTimelineTableEventSeparator,
				trim( $val ) );
			foreach ( $eventList as $eventStr ) {

				$tmp = explode( $wgTimelineTableFieldSeparator,
					trim( $eventStr ) );
				if ( count( $tmp ) >= 2 ) {
					$dateField = explode( $wgTimelineTableDateSeparator,
						trim( $tmp[0] ) );
					switch ( count( $dateField ) ) {
						case 1:
							// Only one entry in date, it must be a year (YYYY)
							if ( preg_match( "/^([0-9]{4})$/", $dateField[0] ) ) {
								return TimelineTableDepthDesc::Year;
							} else {
								return null;
							}
						case 2:
							// Two entries in date, it must be year-month (YYYY-MM)
							if ( preg_match( "/^([0-9]{4})$/", $dateField[0] ) &&
								preg_match( "/^([0-9]{1,2})$/", $dateField[1] )
							) {
								return TimelineTableDepthDesc::Month;
							} else {
								return null;
							}
						case 3:
							// Three entries in date, it must be year-month-day
							// (YYYY-MM-DD)
							if ( preg_match( "/^([0-9]{4})$/", $dateField[0] ) &&
								preg_match( "/^([0-9]{1,2})$/", $dateField[1] ) &&
								preg_match( "/^([0-9]{1,2})$/", $dateField[2] )
							) {
								return TimelineTableDepthDesc::Day;
							} else {
								return null;
							}
						default:
							return null;
					}
				} else {
					return null;
				}
			}
		}

		return null;
	}

	/**
	 * Create table lines for headers
	 *
	 * @param int $level
	 * @param int $depth
	 * @param string $cssClass
	 * @param bool $isHeader
	 * @param string $format
	 * @param string $substr
	 */
	public function addHeader( $level, $depth, $cssClass, $isHeader, $format,
		$substr
	) {
		// Select header/footer mode
		if ( $isHeader ) {
			$k = count( $this->tableHeaderLines );
			$this->tableHeaderLines[$k] = [];
		} else {
			$k = count( $this->tableFooterLines );
			$this->tableFooterLines[$k] = [];
		}

		// Use header style for both header and footer
		$type = 'th';

		switch ( $level ) {

			case TimelineTableDepthDesc::Year:
				$curDate = clone $this->startDate;
				while ( $curDate <= $this->endDate ) {
					$str = $curDate->format( $format );
					$hEvent = new TimelineTableEvent();
					$startDate = clone $curDate;
					$endDate = clone $curDate;
					// TODO: make this more general (first/last day of year)
					TimelineTableDateDiffHelper::getLastDay( $endDate, $str );
					if ( $endDate > $this->endDate ) {
						$endDate = $this->endDate;
					}
					$hEvent->createEvent( $startDate, $endDate, $str, '',
						$cssClass, $type, $substr );
					if ( $isHeader ) {
						array_push( $this->tableHeaderLines[$k], $hEvent );
					} else {
						array_push( $this->tableFooterLines[$k], $hEvent );
					}
					$nextYearStr = strval( intval( $str ) + 1 );
					TimelineTableDateDiffHelper::getFirstDay( $curDate,
															  $nextYearStr );
					unset( $startDate );
					unset( $endDate );
					unset( $hEvent );
				}
				break;

			case TimelineTableDepthDesc::Month:
				$curDate = clone $this->startDate;
				while ( $curDate <= $this->endDate ) {
					$hEvent = new TimelineTableEvent();
					$startDate = clone $curDate;
					$endDate = clone $curDate;
					$endDate->modify( 'last day of this month' );
					if ( $endDate > $this->endDate ) {
						$endDate = $this->endDate;
					}
					$str = $curDate->format( $format );
					$hEvent->createEvent( $startDate, $endDate, $str, '',
						$cssClass, $type, $substr );
					if ( $isHeader ) {
						array_push( $this->tableHeaderLines[$k], $hEvent );
					} else {
						array_push( $this->tableFooterLines[$k], $hEvent );
					}
					$curDate->modify( 'first day of next month' );
					unset( $startDate );
					unset( $endDate );
					unset( $hEvent );
				}
				break;

			case TimelineTableDepthDesc::Day:
				$curDate = clone $this->startDate;
				// Update format if displaying day names and timeline is longer than
				// a week
				if ( ( strcmp( $format, 'D' ) == 0 ||
						strcmp( $format, 'l' ) == 0 ) &&
					$this->endDate->diff( $curDate )->days > 7
				) {
					$flagAddDayNumber = true;
				} else {
					$flagAddDayNumber = false;
				}
				while ( $curDate <= $this->endDate ) {
					$hEvent = new TimelineTableEvent();
					$str = $curDate->format( $format );
					if ( $flagAddDayNumber ) {
						if ( $substr > 0 ) {
							$str = substr( $str, 0, $substr );
						}
						$str .= " " . $curDate->format( "j" );
						$substrEvent = 0;
					} else {
						$substrEvent = $substr;
					}
					$startDate = clone $curDate;
					$endDate = clone $curDate;
					$endDate->setTime( 23, 59, 59 );
					if ( $endDate > $this->endDate ) {
						$endDate = $this->endDate;
					}
					$hEvent->createEvent( $startDate, $endDate, $str, '',
						$cssClass, $type, $substrEvent );
					if ( $isHeader ) {
						array_push( $this->tableHeaderLines[$k], $hEvent );
					} else {
						array_push( $this->tableFooterLines[$k], $hEvent );
					}
					unset( $curDate );
					$curDate = clone $endDate;
					$curDate->modify( '+1 second' );
					unset( $startDate );
					unset( $endDate );
					unset( $hEvent );
				}
				break;

			case TimelineTableDepthDesc::Week:
				$curDate = clone $this->startDate;
				while ( $curDate <= $this->endDate ) {
					$hEvent = new TimelineTableEvent();
					$startDate = clone $curDate;
					// Move to first day of week (Monday)
					$startDayIdx = $startDate->format( "N" ) - 1;
					$endDate = clone $startDate;
					$dayDiff = 6 - $startDayIdx;
					$endDate->modify( '+' . $dayDiff . ' days' );
					if ( $endDate > $this->endDate ) {
						$endDate = $this->endDate;
					}
					$str = $curDate->format( $format );
					$tooltip = $startDate->format( "Y-m-d" ) . " / " .
						$endDate->format( "Y-m-d" );
					$hEvent->createEvent( $startDate, $endDate, $str, $tooltip,
						$cssClass, $type, $substr );
					if ( $isHeader ) {
						array_push( $this->tableHeaderLines[$k], $hEvent );
					} else {
						array_push( $this->tableFooterLines[$k], $hEvent );
					}
					unset( $curDate );
					$curDate = clone $endDate;
					$curDate->modify( '+1 day' );
					unset( $startDate );
					unset( $endDate );
					unset( $hEvent );
				}
				break;

			case TimelineTableDepthDesc::Hour:
				$curDate = clone $this->startDate;
				while ( $curDate <= $this->endDate ) {
					$hEvent = new TimelineTableEvent();
					$startDate = clone $curDate;
					$endDate = clone $curDate;
					$curHour = intval( $endDate->format( 'H' ) );
					$curMinute = intval( $endDate->format( 'i' ) );
					$curSecond = intval( $endDate->format( 's' ) );
					$endDate->setTime( $curHour, 59, 59 );
					if ( $endDate > $this->endDate ) {
						$endDate = $this->endDate;
					}
					$str = $curDate->format( $format );
					$hEvent->createEvent( $startDate, $endDate, $str, '',
						$cssClass, $type, $substr );
					if ( $isHeader ) {
						array_push( $this->tableHeaderLines[$k], $hEvent );
					} else {
						array_push( $this->tableFooterLines[$k], $hEvent );
					}
					unset( $curDate );
					$curDate = clone $endDate;
					$curDate->modify( '+1 second' );
					unset( $startDate );
					unset( $endDate );
					unset( $hEvent );
				}
				break;

			case TimelineTableDepthDesc::Minute:
				$curDate = clone $this->startDate;
				while ( $curDate <= $this->endDate ) {
					$hEvent = new TimelineTableEvent();
					$startDate = clone $curDate;
					$endDate = clone $curDate;
					$curHour = intval( $endDate->format( 'h' ) );
					$curMinute = intval( $endDate->format( 'i' ) );
					$curSecond = intval( $endDate->format( 's' ) );
					$endDate->setTime( $curHour, $curMinute, 59 );
					if ( $endDate > $this->endDate ) {
						$endDate = $this->endDate;
					}
					$str = $curDate->format( $format );
					$hEvent->createEvent( $startDate, $endDate, $str, '',
						$cssClass, $type, $substr );
					if ( $isHeader ) {
						array_push( $this->tableHeaderLines[$k], $hEvent );
					} else {
						array_push( $this->tableFooterLines[$k], $hEvent );
					}
					unset( $curDate );
					$curDate = clone $endDate;
					$curDate->modify( '+1 second' );
					unset( $startDate );
					unset( $endDate );
					unset( $hEvent );
				}
				break;

			case TimelineTableDepthDesc::Second:
				$curDate = clone $this->startDate;
				while ( $curDate <= $this->endDate ) {
					$hEvent = new TimelineTableEvent();
					$startDate = clone $curDate;
					$endDate = clone $curDate;
					$curHour = intval( $endDate->format( 'h' ) );
					$curMinute = intval( $endDate->format( 'i' ) );
					$curSecond = intval( $endDate->format( 's' ) );
					// $endDate->setTime($curHour, $curMinute, 59);
					if ( $endDate > $this->endDate ) {
						$endDate = $this->endDate;
					}
					$str = $curDate->format( $format );
					$hEvent->createEvent( $startDate, $endDate, $str, '',
						$cssClass, $type, $substr );
					if ( $isHeader ) {
						array_push( $this->tableHeaderLines[$k], $hEvent );
					} else {
						array_push( $this->tableFooterLines[$k], $hEvent );
					}
					unset( $curDate );
					$curDate = clone $endDate;
					$curDate->modify( '+1 second' );
					unset( $startDate );
					unset( $endDate );
					unset( $hEvent );
				}
				break;
		}
	}
}
