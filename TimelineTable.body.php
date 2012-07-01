<?php

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if( !defined( 'MEDIAWIKI' ) )
{
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

// Functions
function isLeapYear($year) {
	return ($year%4==0) && ($year%100!=0) || ($year%400==0);
}

function nDaysMonth($month, $year) {
	// Check leap year
	switch ( $month ) {
		case ( $month==1 || $month==3 || $month==5 || $month==7 || $month==8 ||
			   $month==10 || $month==12 ):
			return 31;
			break;
		case ( $month==4 || $month==6 || $month==9 || $month==11):
			return 30;
			break;
		case 2:
			if ( isLeapYear($year) )
			{
				return 29;
			}
			else
			{
				return 28;
			}
			break;
	}
}

function nDaysYear($year) {
	if ( isLeapYear($year) ) {
		return 366;
	} else {
		return 365;
	}
}

// Main function
function efTimelineTableRender( $input, array $args, Parser $parser, PPFrame $frame ) {
	// Parameters
	$wgTimelineTableLineSeparator  = "\n"; // Separator for parsing lines of the input.
	$wgTimelineTableFieldSeparator = "|";  // Separator for parsing fields of a single
	                                       // event.
	$wgTimelineTableDateSeparator  = "-";  // Separator for parsing the date of an event.
	$wgTimelineTableMaxCells       = 100;  // If the total length of the timetable (in
	                                       // days) is larger than this value, do not
	                                       // display days in table.
	$wgHTMLlr                 = "\n"; // Line return (in the rendered html file).
	$wgHTMLtab                = "\t"; // Tabulation (in the rendered html file).

	// Extract parameters from global variables
//	global $wgTimelineTableFieldSeparator;
//	global $wgTimelineTableDateSeparator;
//	global $wgTimelineTableLineSeparator;
//	global $wgTimelineTableMaxCells;
//	global $wgHTMLlr;
//	global $wgHTMLtab;

	// Parse tag arguments
	//$title = $args['title']; // fix from MikaelLindmark
	//$footer = $args['footer'];
	if(isset($args['title'])) {  $title = $args['title'];  } else {  $title = ""; }
	if(isset($args['footer'])){ $footer = $args['footer']; } else { $footer = ""; }

	// Get events
	$lines = explode($wgTimelineTableLineSeparator,trim($input));

	// ---------- Process years (get first and last) ----------
	// --------------------------------------------------------
	$allStartYear = 9999; // Should work for a while
	$allEndYear = -1;
	foreach ( $lines as $val ) {
		$tmp=explode($wgTimelineTableFieldSeparator,trim($val));
		if (count($tmp) >= 2) {
			$year=explode($wgTimelineTableDateSeparator,trim($tmp[0]),2);
			if ( (int)($year[0])<$allStartYear ){$allStartYear = (int)($year[0]);}
			$year=explode($wgTimelineTableDateSeparator,trim($tmp[1]),2);
			if ( (int)($year[0])>$allEndYear ){$allEndYear = (int)($year[0]);}
		}
	}

	// Number of years to display
	$nTotalYears = $allEndYear - $allStartYear + 1;
	// ---------- Process months (get first and last) ----------
	// ---------------------------------------------------------
	$allStartMonth = 13;
	$allEndMonth = 0;
	$flagShowMonths = true;
	foreach ( $lines as $val ) {
		$tmp=explode($wgTimelineTableFieldSeparator,trim($val));
		$eventStart = explode($wgTimelineTableDateSeparator,$tmp[0]);
		if ( sizeof($eventStart)>0 ) {
			$eventStartYear = $eventStart[0];
		} else {
			$flagShowMonths = false;
		}
		if ( sizeof($eventStart)>1 ) {
			$eventStartMonth = $eventStart[1];
		} else {
			$eventStartMonth = 0;
			$flagShowMonths = false;
		}
		$eventEnd = explode($wgTimelineTableDateSeparator,$tmp[1]);
		if ( sizeof($eventEnd)>0 ) {
			$eventEndYear = $eventEnd[0];
 		} else {
			$flagShowMonths = false;
		}
		if ( sizeof($eventEnd)>1 ) {
			$eventEndMonth = $eventEnd[1];
		} else {
			$eventEndMonth = 0;
			$flagShowMonths = false;
		}
		$eventStartYear = ((int)$eventStartYear);
		$eventEndYear = ((int)$eventEndYear);
		$eventStartMonth = (int)$eventStartMonth;
		$eventEndMonth = (int)$eventEndMonth;
		if ( $eventStartMonth==0 || $eventEndMonth==0 ) {
			$flagShowMonths = false;
		}
		if ( $eventStartYear==$allStartYear ) {
			if ( $eventStartMonth<$allStartMonth ) {
				$allStartMonth = $eventStartMonth;
			}
		}
		if ( $eventEndYear==$allEndYear ) {
			if ( $eventEndMonth>$allEndMonth ) {
				$allEndMonth = $eventEndMonth;
			}
		}
	}
	$nMonths[] = 12 - $allStartMonth + 1;
	for ( $year=$allStartYear+1 ; $year<$allEndYear ; $year++ ) {
		$nMonths[] = 12;
	}
	$nMonths[] = $allEndMonth;
	// $nTotalMonths contains the total number of months over the time range
	$nTotalMonths = array_sum($nMonths);

	// ---------- Process days (get first and last) ----------
	// -------------------------------------------------------
	$allStartDay = 32;
	$allEndDay = 0;
	$flagShowDays = $flagShowMonths? true: false;
	foreach ( $lines as $val ) {
		$tmp = explode($wgTimelineTableFieldSeparator,trim($val));
		if (count($tmp) < 2) {
			continue;
		}
		$eventStarttmp = explode($wgTimelineTableDateSeparator,$tmp[0]);
		if (count($eventStarttmp) < 3) {
			continue;
		}
		list($eventStartYear, $eventStartMonth, $eventStartDay) = $eventStarttmp;	
		list($eventEndYear, $eventEndMonth, $eventEndDay) =
			explode($wgTimelineTableDateSeparator,$tmp[1]);
		$eventStartYear = ((int)$eventStartYear);
		$eventEndYear = ((int)$eventEndYear);
		$eventStartMonth = (int)$eventStartMonth;
		$eventEndMonth = (int)$eventEndMonth;
		$eventStartDay = (int)$eventStartDay;
		$eventEndDay = (int)$eventEndDay;
		if ( $eventStartDay==0 || $eventEndDay==0 ) {
			$flagShowDays = false;
		}
		if ( $eventStartYear==$allStartYear &&
			 $eventStartMonth==$allStartMonth ) {
			if ( $eventStartDay<$allStartDay ) {
				$allStartDay = $eventStartDay;
			}
		}
		if ( $eventEndYear==$allEndYear && $eventEndMonth==$allEndMonth ) {
			if ( $eventEndDay>$allEndDay ) {
				$allEndDay = $eventEndDay;
			}
		}
	}
	if ( $allStartYear==$allEndYear && $allStartMonth==$allEndMonth ) {
		$nDays[0] = $allEndDay - $allStartDay + 1;
	}
	elseif ( $allStartYear==$allEndYear ) {
		$nDays[0] = nDaysMonth($allStartMonth,$allStartYear) - $allStartDay + 1;
		for ( $month=$allStartMonth+1 ; $month<$allEndMonth ; $month++ ) {
			$nDays[] = nDaysMonth($month,$allStartYear);
		}
	} else {
		$nDays[0] = nDaysMonth($allStartMonth,$allStartYear) - $allStartDay + 1;
		for ( $month=$allStartMonth+1 ; $month<=12 ; $month++ ) {
		   $nDays[] = nDaysMonth($month,$allStartYear);
		}
		for ( $year=1 ; $year<$nTotalYears-1 ; $year++ ) {
			$year1 = $year + $allStartYear;
			for ( $month=1 ; $month<=12 ; $month++ ) {
				$nDays[] = nDaysMonth($month,$year1);
			}
		}
		for ( $month=1 ; $month<$allEndMonth ; $month++ ) {
			$nDays[] = nDaysMonth($month,$allEndYear);
		}
	}
	$nDays[] = $allEndDay;
	// $nTotalDays contains the total number of days over the time range
	$nTotalDays = array_sum($nDays);

	// ----- Display level (days, months or years) -----
	// -------------------------------------------------
	if ($flagShowMonths) {
		if ( $nTotalDays<$wgTimelineTableMaxCells && $flagShowDays ) {
			$flagShowDays = true;
			$monthList = array( "January" , "February" , "March" , "April" , "May" ,
								"June" , "July" , "August" , "September" , "October" ,
								"November" , "December" );
			$nTotalCells = $nTotalDays;
		} else {
			$flagShowDays = false;
			$monthList = array( "J", "F", "M", "A", "M", "J", "J", "A", "S", "O",
								"N", "D" );
			$nTotalCells = $nTotalMonths;
		}
	} else {
		$nTotalCells = $nTotalYears;
	}

	// ----- Span values -----
	// -----------------------
	// Number of cells in each year
	if ( $flagShowDays ) {
		for ( $year=0 ; $year<$nTotalYears ; $year++ ) {
			$year1 = $year + $allStartYear;
			if ( $year1!=$allStartYear && $year1!=$allEndYear ) {
				$nCellsYear[$year] = nDaysYear($year1);
			}
			elseif ( $year1==$allStartYear && $year1==$allEndYear ) {
				if ( $allStartMonth == $allEndMonth ) {
					$nCellsYear[$year] = $allEndDay - $allStartDay + 1;
				} else {
					$nCellsYear[$year] = nDaysMonth($allStartMonth,$year1) -
						$allStartDay + 1;
					for ( $month=$allStartMonth+1 ; $month<=$allEndMonth ;
						  $month++ ) {
						if ( $month==$allEndMonth ) {
							$nCellsYear[$year] += $allEndDay;
						} else {
							$nCellsYear[$year] += nDaysMonth($month,$year1);
						}
					}
				}
			}
			elseif ( $year1==$allStartYear ) {
				$nCellsYear[$year] = nDaysMonth($allStartMonth,$year1) -
					$allStartDay + 1;
				for ( $month=$allStartMonth+1 ; $month<=12 ; $month++ ) {
					if ( $month==$allEndMonth ) {
						$nCellsYear[$year]+=$allEndDay;
					} else {
						$nCellsYear[$year] += nDaysMonth($month,$year1);
					}
				}
			}
			elseif ( $year1==$allEndYear ) {
				$nCellsYear[$year]=0;
				for ( $month=1 ; $month<=$allEndMonth ; $month++ ) {
					if ( $month==$allEndMonth ) {
						$nCellsYear[$year] += $allEndDay;
					} else {
						$nCellsYear[$year] += nDaysMonth($month,$year1);
					}
				}
			}
		}
	}
	elseif($flagShowMonths) {
		$nCellsYear[0] = 12 - $allStartMonth + 1;
		for ( $year=1 ; $year<$nTotalYears-1 ; $year++) {
			$nCellsYear[$year]=12;
		}
		$nCellsYear[$nTotalYears-1] = $allEndMonth;
	} else {
		for ( $year=0 ; $year<$nTotalYears ; $year++ ) {
			$nCellsYear[$year] = 1;
		}
	}
	// Number of cells in each month (only when displaying days)
	if ( $flagShowDays ) {
		for ( $year=0 ; $year<$nTotalYears ; $year++ ) {
			$year1 = $year + $allStartYear;
			if ( $year1 == $allStartYear ) {
				$monthStart = $allStartMonth;
			} else {
				$monthStart = 1;
			}
			if ( $year1 == $allEndYear ) {
				$monthEnd = $allEndMonth;
			} else {
				$monthEnd = 12;
			}
			for ( $month=$monthStart ; $month<=$monthEnd ; $month++ ) {
				if ( $year==0 && $month==$allStartMonth ) {
					if ( $allStartMonth==$allEndMonth &&
						 $allStartYear==$allEndYear ) {
						$nCellsMonth[] = $allEndDay - $allStartDay + 1;
					} else {
						$nCellsMonth[] = nDaysMonth($month,$year1) -
							$allStartDay + 1;
					}
				}
				elseif ( $year==$nTotalYears-1 && $month==$allEndMonth ) {
					$nCellsMonth[] = $allEndDay;
				} else {
					$nCellsMonth[] = nDaysMonth($month,$year1);
				}
			}
		}
	}

	//----------------------------------------------------------------------------
	// Create the timeline: $timeline_str will contain the html code for the table
	//----------------------------------------------------------------------------

	// Start the table
	$timeline_str = "<table class=tl_table>$wgHTMLlr";
	// Header: title line
	$timeline_str .= "$wgHTMLtab<thead class=tl_header>\n";
	$timeline_str .= "$wgHTMLtab$wgHTMLtab<tr>";
	$timeline_str .= "$wgHTMLlr$wgHTMLtab$wgHTMLtab$wgHTMLtab";
	$timeline_str .= '<th colspan="' . $nTotalCells;
	$timeline_str .= '" class=tl_title>';
	$timeline_str .= htmlspecialchars($title);
	$timeline_str .= "</th>$wgHTMLlr";
	$timeline_str .= "$wgHTMLtab$wgHTMLtab</tr>$wgHTMLlr";

	// Header: Years timeline
	$timeline_str .= "$wgHTMLtab$wgHTMLtab<tr>$wgHTMLlr";
	for ( $year=0 ; $year<$nTotalYears ; $year++ ) {
		$timeline_str .= "$wgHTMLtab$wgHTMLtab$wgHTMLtab";
		$timeline_str .= '<th colspan="' . $nCellsYear[$year];
		$timeline_str .= '" class=tl_years>';
		$timeline_str .= ($year + $allStartYear);
		$timeline_str .= "</th>$wgHTMLlr";
	}
	$timeline_str .= "$wgHTMLtab$wgHTMLtab</tr>$wgHTMLlr";
	if($flagShowMonths) {
		$timeline_str .= "$wgHTMLtab$wgHTMLtab<tr>$wgHTMLlr";
		// Header: Months
		$monthIdx = 0;
		for ( $year=0 ; $year<$nTotalYears ; $year++ ) {
			$year1 = $year + $allStartYear;
			if ( $year1 == $allStartYear ) {
				$monthStart = $allStartMonth;
			} else {
				$monthStart = 1;
			}
			if ( $year1 == $allEndYear ) {
				$monthEnd = $allEndMonth;
			} else {
				$monthEnd = 12;
			}
			for ( $month=$monthStart ; $month<=$monthEnd ; $month++ ) {
				$timeline_str .= "$wgHTMLtab$wgHTMLtab$wgHTMLtab";
				if ( $flagShowDays ) {
					$timeline_str .= '<th colspan="';
					$timeline_str .= $nCellsMonth[$monthIdx];
					$timeline_str .= '" class=tl_months>';
					$monthIdx++;
				} else {
					$timeline_str .= '<th class=tl_months>';
				}
				$timeline_str .= $monthList[$month-1];
				$timeline_str .= "</th>$wgHTMLlr";
			}
		}
		$timeline_str .= "$wgHTMLtab$wgHTMLtab</tr>$wgHTMLlr";
		// Header: Days
		if ( $flagShowDays ) {
			$timeline_str .= "$wgHTMLtab$wgHTMLtab<tr>$wgHTMLlr";
			for ( $year=0 ; $year<$nTotalYears ; $year++ ) {
				$year1 = $year + $allStartYear;
				if ( $year1 == $allStartYear ) {
					$monthStart = $allStartMonth;
				} else {
					$monthStart = 1;
				}
				if ( $year1 == $allEndYear ) {
					$monthEnd = $allEndMonth;
				} else {
					$monthEnd = 12;
				}
				for ( $month=$monthStart ; $month<=$monthEnd ; $month++ ) {
					if ($month==$allStartMonth && $year1==$allStartYear ) {
						$dayStart = $allStartDay;
					} else {
						$dayStart = 1;
					}
					if ($month==$allEndMonth && $year1==$allEndYear ) {
						$dayEnd = $allEndDay;
					} else {
						$dayEnd = nDaysMonth($month,$year1);
					}
					for ( $day=$dayStart ; $day<=$dayEnd ; $day++ ) {
						$timeline_str .= "$wgHTMLtab$wgHTMLtab";
						$timeline_str .= "$wgHTMLtab";
						$timeline_str .= '<th class=tl_days>';
						$timeline_str .= $day;
						$timeline_str .= "</th>$wgHTMLlr";
					}
				}
			}
			$timeline_str .= "$wgHTMLtab$wgHTMLtab</tr>$wgHTMLlr";
		}
	}
	$timeline_str .= "$wgHTMLtab</thead>$wgHTMLlr";

	// Footer
	$timeline_str .= "$wgHTMLtab<tfoot class=tl_footer>";
	$timeline_str .= "$wgHTMLlr$wgHTMLtab$wgHTMLtab<tr>";
	$timeline_str .= "$wgHTMLlr$wgHTMLtab$wgHTMLtab$wgHTMLtab";
	$timeline_str .= '<td colspan="' . $nTotalCells;
	$timeline_str .= '" class=tl_foot>';
	$timeline_str .= htmlspecialchars($footer);
	$timeline_str .= "</td>$wgHTMLlr$wgHTMLtab$wgHTMLtab</tr>";
	$timeline_str .= "$wgHTMLlr$wgHTMLtab</tfoot>$wgHTMLlr";

	// Body: Events (display one event per row)
	$timeline_str .= "$wgHTMLtab<tbody class=tl_body>$wgHTMLlr";
	foreach ( $lines as $val ) {
		$lineTmp = explode($wgTimelineTableFieldSeparator,$val);
		$nFields = count($lineTmp);
		$comment = "";
		$cssStyle = "";
		switch ($nFields)
		{
		case 3:
			list($eventStartDate, $eventEndDate, $text) = $lineTmp;
			break;
		case 4:
			list($eventStartDate, $eventEndDate, $text, $comment) = $lineTmp;
			break;
		case 5:
			list($eventStartDate, $eventEndDate, $text, $comment, $cssStyle) =
				$lineTmp;
			break;
		default:
			continue;
		}
			
		// Parse the event dates and content
		$startDateTmp = explode($wgTimelineTableDateSeparator,
		                        $eventStartDate);
		$endDateTmp = explode($wgTimelineTableDateSeparator,
		                      $eventEndDate);
		if ( $flagShowDays ) {
			if (count($startDateTmp) < 3) {
				continue;
			}
			list($eventStartYear, $eventStartMonth, $eventStartDay) =
				$startDateTmp;
			list($eventEndYear, $eventEndMonth, $eventEndDay) =
				$endDateTmp;
			$eventStartDay = (int)$eventStartDay;
			$eventEndDay = (int)$eventEndDay;
			$eventStartMonth = (int)$eventStartMonth;
			$eventEndMonth = (int)$eventEndMonth;
		} elseif ($flagShowMonths) {
			if (count($startDateTmp) < 2) {
				continue;
			}
			list($eventStartYear, $eventStartMonth) = $startDateTmp;
			list($eventEndYear, $eventEndMonth) = $endDateTmp;
			$eventStartMonth = (int)$eventStartMonth;
			$eventEndMonth = (int)$eventEndMonth;
		} else {
			$eventStartYear = $startDateTmp[0];
			$eventEndYear = $endDateTmp[0];
		}
		$eventStartYear = (int)$eventStartYear;
		$eventEndYear = (int)$eventEndYear;

		// Find the number of cells between the first column of the timeline
		// table and the first cell of the event
		if ( $flagShowDays ) {
			$nPreviousCells = 0;
			$curY = $allStartYear;
			$curM = $allStartMonth;
			$curD = $allStartDay;
			while ( ($curY!=$eventStartYear || $curM!=$eventStartMonth ||
				$curD!=$eventStartDay) && $nPreviousCells<$nTotalCells ) {
				if ( $curM==12 && $curD==nDaysMonth($curM,$curY) ) {
					$curM = 1;
					$curD = 1;
					$curY++;
				}
				elseif ( $curD==nDaysMonth($curM,$curY) ) {
					$curM++;
					$curD = 1;
				} else {
					$curD++;
				}
				$nPreviousCells++;
			}
			$nEventCells = 1;
			// Find the length of the event (in days)
			while ( $curY!=$eventEndYear || $curM!=$eventEndMonth ||
			        $curD!=$eventEndDay ) {
				if ( $curM==12 && $curD==nDaysMonth($curM,$curY) ) {
					$curM = 1;
					$curD = 1;
					$curY++;
				}
				elseif ( $curD==nDaysMonth($curM,$curY) ) {
					$curM++;
					$curD = 1;
				} else {
					$curD++;
				}
				$nEventCells++;
			}
		}
		elseif($flagShowMonths) { // if ( $flagShowDays )
			// $nPreviousCells = 0;
			// $curY = $allStartYear;
			// $curM = $allStartMonth;
			// while ( $curY!=$eventStartYear || $curM!=$eventStartMonth )
			// {
			// 	if ( $curM==12 )
			// 	{
			// 		$curM = 1;
			// 		$curY++;
			// 	}
			// 	else
			// 	{
			// 		$curM++;
			// 	}
			// 	$nPreviousCells++;
			// }
			$nPreviousCells = array_sum(array_slice($nMonths, 0,
			    $eventStartYear-$allStartYear));
			$nPreviousCells += $eventStartMonth - 1;
			if ( $eventStartYear==$allStartYear ) {
				$nPreviousCells -= $allStartMonth;
			} else {
				$nPreviousCells -= 1;
			}
			if ( $nPreviousCells!=0 ) {
				$nPreviousCells++;
			}
			// Find the length of the event (in months)
			$nEventCells = 12 - $eventStartMonth + 1;
			$nEventCells = $nEventCells + $eventEndMonth;
			$nEventCells = $nEventCells + 12*($eventEndYear-$eventStartYear-1);
		} else {
			$nPreviousCells = $eventStartYear - $allStartYear;
			$nEventCells = $eventEndYear - $eventStartYear + 1;
		}

		// Define the number of cells between the end of the event and the end
		// of the timeline table
		$nRemainingCells = $nTotalCells - $nPreviousCells - $nEventCells;

		// Merge the cells before the event into a 'freetime' cell
		$timeline_str .= "$wgHTMLtab$wgHTMLtab<tr>$wgHTMLlr";
		if ( $nPreviousCells > 0 ) {
			$timeline_str .= "$wgHTMLtab$wgHTMLtab$wgHTMLtab";
			$timeline_str .= '<td colspan="' . $nPreviousCells;
			$timeline_str .= '" class=tl_freetime></td>';
			$timeline_str .= "$wgHTMLlr";
		}
		// Create the event cell
		$timeline_str .= "$wgHTMLtab$wgHTMLtab$wgHTMLtab";
		$timeline_str .= '<td colspan="' . $nEventCells;
		$timeline_str .= '" class=tl_event ';
		if ( strcmp(trim($cssStyle),"") ) {
			$timeline_str .= 'style="';
			$timeline_str .= htmlspecialchars($cssStyle) . '"';
		}
		$timeline_str .= ">";
		if( !defined( 'MEDIAWIKI' ) ) {
			$timeline_str .= htmlspecialchars($text);
		} else {
			$timeline_str .= $parser->recursiveTagParse($text);
		}
		if ( strcmp(trim($comment),"") ) {
			$timeline_str .= '<br />(';
			if( !defined( 'MEDIAWIKI' ) ) {
				$timeline_str .= htmlspecialchars($comment);
			} else {
				$parsed_comment = $parser->recursiveTagParse($comment);
				$timeline_str .= $parsed_comment;
			}
			$timeline_str .= ')';
		}
		$timeline_str .= "</td>$wgHTMLlr";
		// Merge the cells after the event into a 'freetime' cell
		if ( $nRemainingCells > 0 ) {
			$timeline_str .= "$wgHTMLtab$wgHTMLtab$wgHTMLtab";
			$timeline_str .= '<td colspan="' . $nRemainingCells;
			$timeline_str .= '" class=tl_freetime></td>';
			$timeline_str .= "$wgHTMLlr";
		}
		$timeline_str .= "$wgHTMLtab$wgHTMLtab</tr>$wgHTMLlr";
	}
	$timeline_str .= "$wgHTMLtab</tbody>$wgHTMLlr";

	// Finish table
	$timeline_str .= "</table><br />$wgHTMLlr";

	// Define the html code as a marker, then change it back to text in
	// 'efTimelineAfterTidy'. This is done to prevent the html code from being
	// modified afterwards.
	global $markerList;
	$makercount = count($markerList);
	$marker = "xx-marker".$makercount."-xx";
	$markerList[$makercount] = $timeline_str;
	return $marker;
}

function efTimelineTableAfterTidy(&$parser, &$text) {
	// find markers in $text
	// replace markers with actual output
	global $markerList;
	for ($i = 0; $i<count($markerList); $i++)
	$text = preg_replace('/xx-marker'.$i.'-xx/',$markerList[$i],$text);
	return true;
}

// TODO
/*
	- Options
	- Check execution time
	- Check inputs (date order, etc.)
*/

