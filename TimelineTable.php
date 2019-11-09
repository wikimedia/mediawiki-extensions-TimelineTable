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
 */
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'TimelineTable' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['TimelineTable'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['TimelineTableAlias'] = __DIR__ . '/TimelineTable.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for the TimelineTable extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the TimelineTable extension requires MediaWiki 1.29+' );
}
