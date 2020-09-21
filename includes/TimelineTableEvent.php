<?php

/**
 * TimelineTable Event object
 */
class TimelineTableEvent {

	/**
	 * @var DateTime|null Start date
	 */
	private $startDate;

	/**
	 * @var DateTime|null End date
	 */
	private $endDate;

	/**
	 * @var int Number of cells in block (only used for freetime block)
	 */
	private $nCells;

	/**
	 * @var string Event description
	 */
	private $text;

	/**
	 * @var string Event comment
	 */
	private $comment;

	/**
	 * @var int Lenght of event text (for headers)
	 */
	private $substr;

	/**
	 * @var string Cell type (td/th)
	 */
	private $cellType;

	/**
	 * @var string Tooltip for event cell
	 */
	private $tooltip;

	/**
	 * @var string Custom CSS-style for event cell
	 */
	private $cellCSSStyle;

	/**
	 * @var string CSS class for event block
	 */
	private $cellCSSClass;

	/**
	 * @var string Event validity
	 */
	private $errMsg = "";

	/**
	 * Parse event from string
	 *
	 * @param string $input
	 * @param string $separator
	 * @return bool
	 */
	public function parse( $input, $separator ) {
		$fields = explode( $separator, trim( $input ) );
		$nFields = count( $fields );
		if ( $nFields >= 2 ) {

			// If date is YYYY (old style date), add "-01-01" to make it
			// parseable by DateTime
			if ( preg_match( "([0-9]{4})", $fields[0] ) ) {
				$fields[0] .= "-01-01";
			}
			// Parse date (return exception message on failure)
			try {
				$this->startDate = new DateTime( $fields[0] );
			} catch ( Exception $e ) {
				$this->errMsg =
					wfMessage( 'timelinetable-error-parsestart',
						$e->getMessage() )->escaped();

				return false;
			}
			// Process second date entry
			if ( preg_match( "([0-9]{4})", $fields[1] ) ) {
				$fields[1] .= "-01-01";
			}
			try {
				$this->endDate = new DateTime( $fields[1] );
			} catch ( Exception $e ) {
				$this->errMsg =
					wfMessage( 'timelinetable-error-parseend',
						$e->getMessage() )->escaped();

				return false;
			}

			// Check that startDate is before endDate
			if ( $this->startDate > $this->endDate ) {
				$this->errMsg =
					wfMessage( 'timelinetable-error-negdate',
						$input )->escaped();

				return false;
			}

			// Read event text / comment / CSS style
			if ( $nFields > 2 ) {
				$this->text = $fields[2];
				if ( $nFields > 3 ) {
					$this->comment = $fields[3];
					if ( $nFields > 4 ) {
						$this->cellCSSStyle = $fields[4];
					}
				}
			}

			// Set other fields
			$this->cellCSSClass = 'tl_event';
			$this->cellType = 'td';
			$this->substr = 0;
			$this->tooltip = $this->startDate->format( "Y-m-d" ) . " / " .
				$this->endDate->format( "Y-m-d" );
		} else {
			// Need at least two dates (start/end) to parse event
			$this->errMsg =
				wfMessage( 'timelinetable-error-parseargs' )->escaped();

			return false;
		}

		return true;
	}

	/**
	 * Create event for table header/footer (e.g. year/month/week/day header)
	 *
	 * @param DateTime $t_startDate
	 * @param DateTime $t_endDate
	 * @param string $t_text
	 * @param string $t_tooltip
	 * @param string $t_class
	 * @param string $t_type
	 * @param int $t_substr
	 */
	public function createEvent( $t_startDate, $t_endDate, $t_text, $t_tooltip,
		$t_class, $t_type, $t_substr = 0 ) {
		$this->startDate = $t_startDate;
		$this->endDate = $t_endDate;
		$this->text = $t_text;
		$this->tooltip = $t_tooltip;
		$this->cellCSSClass = $t_class;
		$this->cellType = $t_type;
		$this->substr = $t_substr;
	}

	/**
	 * Create "event" block for free-time (determined by the number of cells
	 * instead of start/end dates)
	 *
	 * @param int $t_nCells
	 * @param string $t_text
	 * @param string $t_tooltip
	 * @param string $t_class
	 */
	public function createEventBlock( $t_nCells, $t_text, $t_tooltip,
		$t_class ) {
		$this->nCells = $t_nCells;
		$this->text = $t_text;
		$this->tooltip = $t_tooltip;
		$this->cellCSSClass = $t_class;
		$this->cellType = 'td';
		$this->substr = 0;
	}

	/**
	 * Test invalid event
	 *
	 * @return bool
	 */
	public function isValid() {
		return strlen( $this->errMsg ) == 0;
	}

	/**
	 * Get length of event (in number of cells for desired depth)
	 *
	 * @param int $depth
	 * @return int|null
	 */
	public function getNumCells( $depth ) {
		// Determine number of cells in current block
		if ( $this->nCells > 0 ) {
			$nEventCells = $this->nCells;
		} else {
			$nEventCells = TimelineTableDateDiffHelper::getNumCells(
				$this->startDate, $this->endDate, $depth );
			if ( $nEventCells === null ) {
				wfDebugLog( "", "Trying to render empty event\n" );
			}
		}
		return $nEventCells;
	}

	/**
	 * Render HTML cell in table
	 *
	 * @param Parser $parser
	 * @param int $depth
	 * @param bool $flagVert
	 * @return string
	 */
	public function render( $parser, $depth, $flagVert = false ) {
		$spanDir = ( $flagVert ) ? 'rowspan' : 'colspan';

		// Determine number of cells in current block
		$nEventCells = $this->getNumCells( $depth );

		// Create the event cell
		$cellopts = [ $spanDir => $nEventCells,
			'class' => $this->cellCSSClass ];
		if ( strcmp( trim( $this->cellCSSStyle ), "" ) ) {
			$cellopts['style'] = htmlspecialchars( $this->cellCSSStyle );
		}
		if ( strcmp( trim( $this->tooltip ), "" ) ) {
			$cellopts['title'] = htmlspecialchars( $this->tooltip );
		}
		$celltext = $parser->recursiveTagParse( $this->text );

		// Add comment field ($substr should not be defined when $comment is,
		// $substr is for headers only)
		if ( strcmp( trim( $this->comment ), "" ) ) {
			$celltext .= '<br />(';
			$parsed_comment = $parser->recursiveTagParse( $this->comment );
			$celltext .= $parsed_comment;
			$celltext .= ')';
		} elseif ( $this->substr > 0 ) {
			// Perform substring if necessary
			$celltext = substr( $celltext, 0, $this->substr );
		}

		// Create table cell
		return Html::rawElement( $this->cellType, $cellopts, $celltext );
	}

	/**
	 * @return string
	 */
	public function getErrorMsg() {
		return $this->errMsg;
	}

	/**
	 * @return DateTime|null
	 */
	public function getStartDate() {
		return $this->startDate;
	}

	/**
	 * @return DateTime|null
	 */
	public function getEndDate() {
		return $this->endDate;
	}
}
