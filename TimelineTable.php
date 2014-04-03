<?php
/**
 * TimelineTable - this extension creates a timeline contained in a simple HTML
 * table.
 *
 * To activate this extension, add the following into your LocalSettings.php file:
 * require_once('$IP/extensions/timelinetable.php');
 *
 * @ingroup Extensions
 * @author Thibault Marin
 * @link http://www.mediawiki.org/wiki/Extension:TimelineTable
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 * @revision
 * 1.7.1 -> 1.8.0
 *  Now using JSON i18n.
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
if( !defined( 'MEDIAWIKI' ) )
{
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['validextensionclass'][] = array(
	'name'         => __FILE__,
	'version'      => '1.8.0',
	'author'       => 'Thibault Marin',
	'url'          => 'http://www.mediawiki.org/wiki/Extension:TimelineTable',
	'description'  => 'Create a table containing a timeline'
);

//Avoid unstubbing $wgParser on setHook() too early on modern (1.12+) MW
//versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'efTimelineTableParserInit';
	$wgHooks['ParserAfterTidy'][]='efTimelineTableAfterTidy';
	$wgExtensionFunctions[] = 'efTimelineTableParserInit';
} else { // Otherwise do things the old fashioned way
	$wgHooks['ParserAfterTidy'][]='efTimelineTableAfterTidy';
	$wgExtensionFunctions[] = 'efTimelineTableParserInit';
}

function efTimelineTableParserInit() {
	global $wgParser;
	$wgParser->setHook( 'timelinetable', 'efTimelineTableRender' );
	return true;
}

$wgMessagesDirs['TimelineTable'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['TimelineTable'] = dirname( __FILE__ ) . '/TimelineTable.i18n.php';
require_once dirname(__FILE__) . '/TimelineTable.body.php';
