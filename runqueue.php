<?php
define( 'STATUS_WAITING', 0 );
define( 'STATUS_ERROR', 1 );
define( 'STATUS_DONE', 1 );

// check if media target folder is writeable
$storageDir = '/media/aggregateshares/';
if ( !is_writeable( $storageDir ) )
{
	echo "$storageDir can not be written to. Wrong user maybe ?\n";
	die();
}

// get next command from DB
$db = new PDO( 'sqlite:///var/www/mkvmanager/tmp/mergequeue.db' );
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sudo = "sudo -u media";
$query = "SELECT `time`, `command`, `status` FROM `commands` WHERE `status` = " . STATUS_WAITING . " ORDER BY `time` DESC";
$stmt = $db->prepare( $query );
$stmt->execute();
while( $row = $stmt->fetch() )
{
	$result = '';
	$return = '';
	extract( $row );

	// @todo Extract target, sources etc using the same code than mkvmerge.php
	echo "[" . date('H:i:s') . "] Starting conversion\n";
	exec( "$sudo $command", $result, $return );
	echo "[" . date('H:i:s') . "] Conversion finished\n";

	unset( $result, $return );

	$return = $db->quote( $return );
	$sth = $db->prepare( 'UPDATE `commands` SET `status` = 1, message = \':return\' WHERE `time` = :time' );
	$sth->execute( array( ':return' => $return, ':time' => $time ) );
}
?>