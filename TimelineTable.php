<?php
/**
 * TimelineTable - this extension creates a timeline contained in a simple HTML
 * table.
 *
 * To activate this extension, add the following into your LocalSettings.php file:
 * require_once('$IP/extensions/TimelineTable.php');
 *
 * @ingroup Extensions
 * @author Thibault Marin
 * @link https://www.mediawiki.org/wiki/Extension:TimelineTable
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 * @revision
 * 2.0 -> 2.1
 *  Fix internationalization, error messages
 *  Add support for time
 *  Vertical table option
 *  Requires MediaWiki 1.23 or later
 * 1.8.1 -> 2.0
 *  Re-organize code into classes
 *  More flexible input format ([http://www.php.net/manual/en/datetime.formats.php])
 *  Allow multiple events per line (must be in increasing order and cannot overlap)
 *  New option format to control headers display (e.g "headers=Y/M footers=D-D")
 *  New selection of depth of table (e.g. <timelinetable ... depth=day ...>)
 *  Support for week level (display week number in year, week dates in tooltip)
 *  Tried to maintain backwards compatibility (except for the
 *    $wgTimelineTableMaxCells option which is now fully deprecated)
 * 1.8 -> 1.8.1
 *  Option to show day names (daynames=1)
 *  Option to hide months (nomonths=1)
 *  Now using JSON i18n.
 * 1.7.1 -> 1.8
 *  Cleanup for mediawiki review
 *  Option to hide years (noyears=1)
 * 1.7 -> 1.7.1
 *  Rename tag to avoid conflict.
 * 1.6 -> 1.7
 *  Fix bug in months processing (when using only years).
 * 1.5 -> 1.6
 *  Support for timeline using years only.
 *  Deprecated split() function replaced by explode().
 * 1.0 -> 1.5
 *  Added ability to display days level.
 *  Reviewed code.
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

/**
 * Extension credits that will show up on Special:Version
 */
$wgExtensionCredits['parserhook'][] = array(
	'name' => 'TimelineTable',
	'path' => __FILE__,
	'version' => '2.1',
	'author' => 'Thibault Marin',
	'url' => 'https://www.mediawiki.org/wiki/Extension:TimelineTable',
	'descriptionmsg' => 'timelinetable-desc'
);

/**
 * Extension class
 */
$wgAutoloadClasses['TimelineTableHooks'] =
	dirname( __FILE__ ) . '/TimelineTable.Hooks.php';
$wgAutoloadClasses['TimelineTableEvent'] =
	dirname( __FILE__ ) . '/TimelineTable.Event.php';
$wgAutoloadClasses['TimelineTableDepthDesc'] =
	dirname( __FILE__ ) . '/TimelineTable.DateDiffHelper.php';
$wgAutoloadClasses['TimelineTableDateDiffHelper'] =
	dirname( __FILE__ ) . '/TimelineTable.DateDiffHelper.php';
$wgAutoloadClasses['TimelineTableTable'] =
	dirname( __FILE__ ) . '/TimelineTable.Table.php';

/**
 * Register hooks
 */
$wgHooks['ParserFirstCallInit'][] =
	'TimelineTableHooks::efTimelineTableParserInit';
$wgHooks['ParserAfterTidy'][] = 'TimelineTableHooks::efTimelineTableAfterTidy';

/**
 * Internationalization
 */
$wgMessagesDirs['TimelineTable'] = __DIR__ . '/i18n';

/**
 * Parameters (modify in LocalSettings.php)
 */

// Separator for parsing lines of the input.
$wgTimelineTableLineSeparator = PHP_EOL; //"\n";

// Separator for parsing fields of a single event
// (default "|" e.g. "date-start|date-end|text|style").
$wgTimelineTableFieldSeparator = "|";

// Separator for events within the same line
$wgTimelineTableEventSeparator = "#";

// Separator for parsing the date of an event (old style events, for backward
// compatibility only).
// (default: "-" e.g. "MM-DD-YYYY")
$wgTimelineTableDateSeparator = "-";

// Set this flag to true to abbreviate month names (see 'strftime' doc)
$wgTimelineTableAbbrMonth = false;

// Length of month/day name in compact mode (e.g. when displaying many years)
$wgTimelineTableShortMonthLen = 1;

