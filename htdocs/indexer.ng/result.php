<?php
require_once( 'config.inc.php' );

include( 'groups.inc.php' );
$isAdmin = @inGroup( 'admin', $_SERVER['REMOTE_USER'] );
$isEditor = @inGroup( 'editor', $_SERVER['REMOTE_USER'] );
$bestand = getBestand( $_SERVER['REMOTE_USER'] );
//var_dump( $bestand );

//if( $isAdmin || $isEditor ) {
	require_once( $indexerPath.'/db.inc.php' );
//}

//require_once( 'helper.inc.php' );

$resultFields = array(
	'id',
	'mimetype',
	'status.locked',
	'status.status',
	'bestand.id',
	'bestand.name',
	'session.id',
	'session.name',
	'session.basepath',
	'session.localpath',
	'session.group',
	'archive.id',
	'archive.name',
	'file.id',
	'file.name',
	'file.localcopy',
	'file.path',
	'file.filesize',
	'file.extension',
	'file.filectime',
	'file.filemtime',
	'file.filetype',
	'file.stat',
	'file.sha256',
	'file.archivetime',
	'file.inventory',
	'gvfs_info.mimetype',
	'gvfs_info.fullinfo',
	'libmagic.mimetype',
	'libmagic.mimeencoding',
	'libmagic.description',
	'siegfried.id',
	'siegfried.format',
	'siegfried.gzdata',
	'tika.fullinfo',
	'imagick.fullinfo',
	'avconv.fullinfo',
	'nsrl.found',
	'nsrl.ProductCode',
	'nsrl.ProductName',
	'nsrl.ProductVersion',
	'nsrl.ApplicationType',
	'nsrl.Language',
	'nsrl.MfgName',
	'nsrl.OpSystemName',
	'nsrl.OpSystemVersion',
	'nsrl.OpMfgName',
	'thumb',
	'shorttext',
);

?><script>
 $(function() {
var name = $( "#name" ),
email = $( "#email" ),
password = $( "#password" ),
allFields = $( [] ).add( name ).add( email ).add( password ),
tips = $( ".validateTips" );
function updateTips( t ) {
tips
.text( t )
.addClass( "ui-state-highlight" );
setTimeout(function() {
tips.removeClass( "ui-state-highlight", 1500 );
}, 500 );
}
$( "#dialog-form" ).dialog({
	autoOpen: false,
	height: 500,
	width: 400,
	modal: true,
	draggable: false,
	buttons: {
		"Set keywords": function() {
			$( this ).dialog( "close" );
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	},
	close: function() {
	}
	});
	$( "#create-user" ).button().click(function() {
		$( "#dialog-form" ).dialog( "open" );
	});
});
</script>
<?php

$limit = array_key_exists( 'limit', $_REQUEST ) ? intval( $_REQUEST['limit'] ) : 10;
$currPage = array_key_exists( 'page', $_REQUEST ) ? intval( $_REQUEST['page'] ) : 0;
$_query = array_key_exists( 'query', $_REQUEST ) ? $_REQUEST['query'] : array();
$_query_plain = array_key_exists( 'query_plain', $_REQUEST ) ? $_REQUEST['query_plain'] : '*:*';
$do_plain = array_key_exists( 'do_plain', $_REQUEST ) ? intval( $_REQUEST['do_plain'] ) : 0;

$solarium = new Solarium\Client( $solarium_config );
$select = $solarium->createSelect();
$helper = $select->getHelper();


$hlq = null;
if( !$do_plain ) {
	$_q = buildQuery( $_query, $hlq, $helper );
}
else {
	$_q = $_query_plain;
}
if( !strlen( $_q )) $_q = '*:*';

if( count( $bestand ) == 0 ) {
	echo "<h2>no rights for user {$_SERVER['REMOTE_USER']}</h2>\n";
	exit();
}

$start = ($currPage-1)*$limit;
$select->setQuery( $_q )
	->setStart( $start )
	->setRows( $limit )
	->setFields( $resultFields );

	if( !in_array( 0, $bestand )) {
		$bestandfilter = "bestand.id:(".implode( ' OR ', $bestand ).")";
		$select->createFilterQuery('bestand')->setQuery($bestandfilter);
		echo $bestandfilter;
	}

if( $hlq )
{
	$hl = $select->getHighlighting();
	$hl->setFields( 'suggest' )
		->setQuery( $hlq )
		->setSnippets( 5 )
		->setFragSize( 2048 );
}

$hasError = false;
try {

	$resultset = $solarium->select( $select );
}
catch( \Solarium\Exception\HttpException $e )
{
	if( !$hlq )
	{
		echo "\n<code>\n{$_q}\n</code><p />\n";
		echo "\n<code>\n{$e}\n</code>\n";
		exit;
	}
	$hasError = true;
	echo "\n<code>\nSOLR Error: trying again without highlighting\n</code><p />\n";
}
if( $hasError )
{
	$hlq = null;
	$select = $solarium->createSelect();
	$select->setQuery( $_q )
		->setStart( $start )
		->setRows( $limit )
		->setFields( $resultFields );
	try {
		//$rb = $select->getRequestBuilder();
		//$qqq = $rb->getRawData( $select );
		$resultset = $solarium->select( $select );
	}
	catch( \Solarium\Exception\HttpException $e )
	{
		echo "\n<code>\n{$_q}\n</code><p />\n";
		echo "\n<code>\n{$e}\n</code>\n";
		exit;
	}

}
	$highlighting = ( $hlq ? $resultset->getHighlighting() : null );
	$numFound = $resultset->getNumFound();

	$qtime = $resultset->getQueryTime();

	$maxPage = ceil( $numFound / $limit );
	$qtime/=1000;

//	echo "<code>{$numFound} Ergebnisse ({$qtime} Sekunden) ".htmlspecialchars( $_q )."</code>\n";
?>
	<div class="well well-sm">
		<?php echo "{$numFound} Ergebnisse ({$qtime} Sekunden)"; ?>
		<input type="text" size="100" id="query_plain" name="query_plain" value="<?php echo htmlspecialchars( $_q ); ?>">
		<input type="submit" name="plain" value="Expertensuche" onClick="$('#do_plain').val( 1 ); execSearch( 1 );" >
		<input type="hidden" name="do_plain" value="<?php echo $do_plain; ?>" id="do_plain">
		<?php if( $do_plain ) echo '<img width="20px" src="icons/ampel/green.png">'; ?>
	</div>
<?php
	include( 'paging.inc.php' );
	?>
	<div id="accordion" class="panel-group">

	<?php
	$num = 0;
	foreach( $resultset as $document )
	{
		$num++;
		$doc = $document->getFields();
		$id = $doc['id'];
		echo "<!-- #{$id} ------------------------------------------------------------------- -->\n";
		$bestandid = $doc['bestand.id'];
		$session = $doc['session.id'];
		$groups = $doc['session.group'];
		$fileid = $doc['file.id'];
		$filename = $doc['file.name'];
		$path = $doc['file.path'];
		$localcopy = $doc['file.localcopy'];
		$localpath = $doc['session.localpath'];
		if( !strlen( trim( $path ))) $path = '/';
		$size = intval( $doc['file.filesize'] );
		$fullpath = "{$doc['session.basepath']}/{$doc['file.path']}/{$doc['file.name']}";
		//$path = substr( $fullpath, 0, -strlen( $filename ));
		$hasTIKA = strlen( @trim( $doc['tika.fullinfo'] )) > 0;
		$hasArchive = strlen( @trim( $doc['archive.id'] )) > 0;
		$hasIMAGICK = strlen( @trim( $doc['imagick.fullinfo'] )) > 0;
		$hasAVCONV = strlen( @trim( $doc['avconv.fullinfo'] )) > 0;
		$hasGVFS = strlen( @trim( $doc['gvfs_info.fullinfo'] )) > 0;
		$hasSiegfried = count( $doc['siegfried.id'] ) > 0;
		$hasNSRL = $doc['nsrl.found'] == true ;
		$hasThumb = strlen( @trim( $doc['thumb'] )) > 0;
		$localfile = $doc['session.localpath'].'/'.$doc['file.localcopy'];
		$hasFile = is_file( $localfile );
		$locked = $doc['status.locked'];
		$status = $doc['status.status'];
		$inventory = isset( $doc['file.inventory'] ) ? $doc['file.inventory'] : 'none';

		$isAdmin = @inGroup( 'admin', $_SERVER['REMOTE_USER'], $bestandid );
		$isEditor = @inGroup( 'editor', $_SERVER['REMOTE_USER'], $bestandid );


		if( strlen( trim( $inventory )) == 0 ) $inventory = 'none';
		?>
		<!--
		<?php echo htmlspecialchars(print_r( $document, true )); ?>
		-->
		<?php
		?>
		<?php

		if( !$fileid )
		{
?>
			<div class="panel panel-default">
				<div class="panel-heading" style="vertical-align: top; width: 100%; min-height: 115px;">
					<b>#<?php echo $id; ?> ERROR!!!</b>
				</div>
			</div>
<?php
		}
		else
		{
			if( $isAdmin || $isEditor ) {
				$sql = "SELECT `lock`, `status` FROM `file` WHERE sessionid={$session} AND fileid={$fileid}";
				$row = $db->getRow( $sql );
				$locked = $row['lock'];
				$status = $row['status'];
			}
			if( !$isAdmin && $locked )
			{
	?>
						<div class="panel panel-default">
							<div class="panel-heading" style="vertical-align: top; width: 100%; min-height: 115px;">
								<b>#<?php echo $id; ?> locked</b>
							</div>
						</div>
	<?php
				continue;
			}
		}
		$icon = 'icons/mimetypes/gnome-mime-application-x-reject.svg';

		if( $hasGVFS )
		{
			if( preg_match( '/standard::icon: .*, gnome-mime-([a-z-]*), .*/', $doc['gvfs_info.fullinfo'], $matches ))
			{
				$parts = explode( '-', 'gnome-mime-'.$matches[1] );
				if( file_exists( 'icons/mimetypes/'.implode( '-', $parts ).'.svg' ))
				{
					$icon = 'icons/mimetypes/'.implode( '-', $parts ).'.svg';
				}
				else
				{
					array_pop( $parts );
					if( file_exists( 'icons/mimetypes/'.implode( '-', $parts ).'.svg' ))
					{
						$icon = 'icons/mimetypes/'.implode( '-', $parts ).'.svg';
					}
				}
			}
		}


		if( strlen( $doc['gvfs_info.mimetype'] ))
		{
			$mimetype = $doc['gvfs_info.mimetype'];
			if( $mimetype == 'text/plain' ) $mimetype = $doc['libmagic.mimetype'];
		}
		else
		{
			$mimetype = $doc['libmagic.mimetype'];
		}

	?>

		<div class="panel panel-default">
			<div class="panel-heading" style="vertical-align: top; width: 100%; min-height: 115px;">
				<div style="width: 150px; height: 110px; float: left;">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $num; ?>">
			<?php if( $hasThumb ) { ?>
					<img style="box-shadow: 3px 3px 4px #000;" src="data:image/jpg;base64,<?php echo $doc['thumb']; ?>" alt="thumb" />
			<?php }  else { ?>
					<img style="" src="<?php echo $icon; ?>" width=68 alt="icon" />
	<?php } ?>
				</a>
				</div>
				<div class="panel-title" style="font-size: 125%;">
				<!-- session.group -->
				<span class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'group:<?php echo htmlspecialchars( $doc['session.group'][0] ); ?>' );"><?php echo implode( '/', $doc['session.group'] ); ?></a></span>

				<!-- session.id -->
				<span class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'session:<?php echo htmlspecialchars( $doc['session.id'] ); ?>' );"><?php echo $doc['session.name']; ?></a></span>


				<!-- unique identifier -->
				<span style="color:#000000;" class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'checksum:<?php echo htmlspecialchars( $doc['file.sha256'] ); ?>' );">#<?php echo $doc['id']; ?></a></span>

				<!-- file.archivetime -->
				<?php
					$date = new \DateTime( $doc['file.archivetime'] );
				?>
				<span class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'itime:<?php echo $date->format( "Y-m-d"  ); ?>' );">
				<?php echo $doc['file.archivetime']; ?>
				</a></span>


				<!-- download -->
				<?php if( $hasFile && $status != 'yellow' && $status != 'red' ) { ?><a href="raw.php?file=<?php echo urlencode( $localfile ); ?>&mime=<?php echo urlencode( $mimetype ); ?>&name=<?php echo urlencode( $doc['file.name'] ); ?>" target="_new">
				<span class="glyphicon glyphicon-download"></span></a> <?php } ?>
				<?php if( $doc['file.filetype'] == 'dir' ) { ?><a style="" href="javascript:newquery( 'session:<?php echo htmlspecialchars( $doc['session.id'] ); ?> path:<?php echo htmlspecialchars( str_replace( ' ', '+', str_replace( '+', "\\+", ($path=='/'?'':$path)."/{$filename}" ))); ?>' );">
				<span class="glyphicon glyphicon-list"></span></a> <?php } ?>

				<!-- status -->
				<div style="display: inline-block;" id="ampel<?php echo str_replace( '.', '', $id ); ?>">
				<?php
				if( $isAdmin || $isEditor ) {
				if( $isAdmin ) {
				?>
					<a href="#" onClick="updateStatus( '<?php echo $id; ?>', '<?php echo $locked ? 'unlock' : 'lock'; ?>' );">
						<img width="24px" src="icons/ampel/<?php echo $locked ? 'locked' : 'unlocked'; ?>.png">
					</a>
				<?php }	else { ?>
					<img width="24px" src="icons/ampel/<?php echo $locked ? 'locked' : 'unlocked'; ?>.png">
				<?php
				}
				}
				if( $isAdmin || $isEditor ) {
				?>
					<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'red' );">
						<img width="24px" src="icons/ampel/red<?php echo $status == 'red' ? '' : '_disabled'; ?>.png">
					</a>
					<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'yellow' );">
						<img width="24px" src="icons/ampel/yellow<?php echo $status == 'yellow' ? '' : '_disabled'; ?>.png">
					</a>
					<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'green' );">
						<img width="24px" src="icons/ampel/green<?php echo $status == 'green' ? '' : '_disabled'; ?>.png">
					</a>
					<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'unknown' );">
						<img width="24px" src="icons/ampel/unknown<?php echo $status == 'unknown' ? '' : '_disabled'; ?>.png">
					</a>
				<?php }	else { ?>
					<img width="24px" src="icons/ampel/red<?php echo $status == 'red' ? '' : '_disabled'; ?>.png">
					<img width="24px" src="icons/ampel/yellow<?php echo $status == 'yellow' ? '' : '_disabled'; ?>.png">
					<img width="24px" src="icons/ampel/green<?php echo $status == 'green' ? '' : '_disabled'; ?>.png">
					<img width="24px" src="icons/ampel/unknown<?php echo $status == 'unknown' ? '' : '_disabled'; ?>.png">
				<?php } ?>
				</div>
				<div style="display: inline-block;" id="inventory<?php echo str_replace( '.', '', $id ); ?>">
					<?php
					if( $isAdmin || $isEditor ) {
						?>
						<span class="label label-default">
							<a style="color:#000000;" href="javascript:updateInventar( '<?php echo $id; ?>', '<?php echo $inventory == 'none' ? '' : $inventory; ?>' );">
								#<?php echo $inventory; ?>
							</a>
						</span>
						<?php
					}
					else {
						?>
						<span class="label label-default">
								#<?php echo $inventory; ?>
						</span>
						<?php

					}
					?>
				</div>
				<br />

				<!-- file.extension -->
				<span class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'ext:<?php echo htmlspecialchars( $doc['file.extension'] ); ?>' );"><?php echo str_replace( ' ', '&nbsp;', sprintf( "% 3s", $doc['file.extension'] )); ?></a></span>

				<!-- file.name -->
				<a style="color:#000000;" href="javascript:newquery( 'name:<?php echo htmlspecialchars( str_replace( ' ', '+', str_replace( '+', "\\+", $filename ))); ?>' );">
				<b style="font-size: 14px; "><?php echo htmlspecialchars( $filename ); ?></b>
				</a>

				<!-- mimetype -->
				<span class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'mime:<?php echo htmlspecialchars( $mimetype ); ?>' );"><?php echo $mimetype; ?></a></span>

				<!-- file.size -->
				<span class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'size:<?php echo $size; ?>' );">
				<?php echo _format_bytes( $size ); ?></a></span>


				<!-- file.mtime -->
				<?php
					$date = new \DateTime( $doc['file.filemtime'] );
				?>
				<span class="label label-default"><a style="color:#000000;" href="javascript:newquery( 'mtime:<?php echo $date->format( "Y-m-d"  ); ?>' );">
				<?php echo $doc['file.filemtime']; ?>
				</a></span>
				&nbsp;
				<!-- Siegfried -->
				<?php
					if( $hasSiegfried ) {
						$siegfried_id = is_array($doc['siegfried.id']) ? $doc['siegfried.id'][0] : $doc['siegfried.id'];
						$siegfried_format = $doc['siegfried.format'];
				?>
				<span class="label label-default">
					<a style="color:#000000;" href="javascript:newquery( 'pronom:<?php echo substr( $siegfried_id, strlen( 'pronom:')); ?>' );">
						<?php echo substr( $siegfried_id, strlen( 'pronom:')); ?>
					</a>
			 </span>
			 &nbsp;
			 <span class="label label-default">
				 <a style="color:#000000;" href="javascript:newquery( 'format:<?php echo str_replace( ' ', '+', htmlspecialchars( $siegfried_format )); ?>' );">
					 <?php echo htmlspecialchars( $siegfried_format ); ?>
				 </a>
			</span>
			<?php } ?>
			<!-- Archiv -->
			<?php
				if( $hasArchive ) {
					$archive_id = $doc['archive.id'];
					$archive_name = $doc['archive.name'];
			?>
			<span class="label label-default">
				<a style="color:#000000;" href="javascript:newquery( 'archiveid:#<?php echo $archive_id; ?>' );">
					<?php echo htmlentities("<".$archive_name.">"); ?>
				</a>
		 </span>
		<?php } ?>
				<!-- file.path -->
				<div style="padding-left: 24px;">
					<a style="color:#909090;" href="javascript:newquery( 'session:<?php echo htmlspecialchars( $doc['session.id'] ); ?> path:<?php echo htmlspecialchars( str_replace( ' ', '+', str_replace( '+', "\\+", $path ))); ?>' );"><?php echo htmlspecialchars( $path ); ?></a>
				</div>
					<!-- suggest -->
					<div style="color: black;">
	<?php
		$highlightedDoc = $hlq ? $highlighting->getResult($id) : null;
		if( $highlightedDoc && (($status != 'yellow' && $status != 'red') || $isAdmin ))
		{
			foreach( $highlightedDoc->getField( 'suggest' ) as $str )
			{
				$str = str_replace( '&lt;/em&gt;', '</em>', str_replace( '&lt;em&gt;', '<em style="background-color: #d0d0d0;">', htmlspecialchars( $str )));
				echo "&bdquo;".trim( $str )."&ldquo;<br />\n";
			}
		}
	?>
				<p />
					</div>

				</div>
    </div>
    <div id="collapse<?php echo $num; ?>" class="panel-collapse collapse">
      <div class="panel-body">
					<ul class="nav nav-tabs" id="fileTab<?php echo $num; ?>">
						<li class="active"><a href="#content<?php echo $num; ?>">content</a></li>
						<li><a href="#file<?php echo $num; ?>">file</a></li>
						<li><a href="#libmagic<?php echo $num; ?>">libmagic</a></li>
						<?php if( $hasGVFS ) { ?><li><a href="#gvfs_info<?php echo $num; ?>">gvfs-info</a></li><?php } ?>
						<?php if( $hasTIKA ) { ?><li><a href="#tika<?php echo $num; ?>">tika</a></li><?php } ?>
						<?php if( $hasIMAGICK ) { ?><li><a href="#imagick<?php echo $num; ?>">imagick</a></li><?php } ?>
						<?php if( $hasAVCONV ) { ?><li><a href="#avconv<?php echo $num; ?>">avconv</a></li><?php } ?>
						<?php if( $hasNSRL ) { ?><li><a href="#nsrl<?php echo $num; ?>">nsrl</a></li><?php } ?>
						<?php if( $hasSiegfried ) { ?><li><a href="#siegfried<?php echo $num; ?>">siegfried</a></li><?php } ?>
						<li><a href="#cite<?php echo $num; ?>">cite this item</a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="content<?php echo $num; ?>"><pre style="border: none;"><?php if(($status != 'yellow' && $status != 'red') || $isAdmin) { echo ( htmlspecialchars( substr( $doc['shorttext'], 0, 1024 ))); } ?></pre>&nbsp;</div>
						<div class="tab-pane" id="file<?php echo $num; ?>"><pre style="border: none;"><?php echo htmlspecialchars( $doc['file.stat'] ); ?></pre></div>
						<div class="tab-pane" id="libmagic<?php echo $num; ?>" style=""><pre style="border: none;">
	<b>mimetype: </b><?php echo htmlspecialchars( $doc['libmagic.mimetype'] ); ?>
	<b>mimeencoding: </b><?php echo htmlspecialchars( $doc['libmagic.mimeencoding'] ); ?>
	<b>description: </b><?php echo htmlspecialchars( $doc['libmagic.description'] ); ?></pre></dl>
						</div>
						<?php if( $hasGVFS ) { ?>
						<div class="tab-pane" id="gvfs_info<?php echo $num; ?>"><pre style="border: none;"><?php echo htmlspecialchars( $doc['gvfs_info.fullinfo'] ); ?></pre></div>
						<?php } ?>
						<?php if( $hasTIKA ) { ?>
						<div class="tab-pane" id="tika<?php echo $num; ?>"><pre style="border: none;"><?php echo htmlspecialchars( $doc['tika.fullinfo'] ); ?></pre></div>
						<?php } ?>

						<?php if( $hasIMAGICK ) { ?>
						<div class="tab-pane" id="imagick<?php echo $num; ?>">
							<pre style="border: none;"><?php echo htmlspecialchars( $doc['imagick.fullinfo'] ); ?></pre>
						</div>
						<?php } ?>
						<?php if( $hasAVCONV ) { ?>
						<div class="tab-pane" id="avconv<?php echo $num; ?>">
							<pre style="border: none;"><?php echo htmlspecialchars( $doc['avconv.fullinfo'] ); ?></pre>
						</div>
						<?php } ?>
						<?php if( $hasNSRL ) { ?>
						<div class="tab-pane" id="nsrl<?php echo $num; ?>"><pre style="border: none;">
<b>ProductCode: </b><?php echo htmlspecialchars( $doc['nsrl.ProductCode'] ); ?><br />
<b>ProductName: </b><?php echo htmlspecialchars( $doc['nsrl.ProductName'] ); ?><br />
<b>ProductVersion: </b><?php echo htmlspecialchars( $doc['nsrl.ProductVersion'] ); ?><br />
<b>ApplicationType: </b><?php echo htmlspecialchars( $doc['nsrl.ApplicationType'] ); ?><br />
<b>Language: </b><?php echo htmlspecialchars( $doc['nsrl.Language'] ); ?><br />
<b>MfgName: </b><?php echo htmlspecialchars( $doc['nsrl.MfgName'] ); ?><br />
<b>OpSystemName: </b><?php echo htmlspecialchars( $doc['nsrl.OpSystemName'] ); ?><br />
<b>OpSystemVersion: </b><?php echo htmlspecialchars( $doc['nsrl.OpSystemVersion'] ); ?><br />
<b>OpMfgName: </b><?php echo htmlspecialchars( $doc['nsrl.OpMfgName'] ); ?></pre></div>
						<?php } ?>
						<?php if( $hasSiegfried ) {
							$fullsiegfried = json_decode(gzdecode( base64_decode( $doc['siegfried.gzdata'])), true );
							?>
						<div class="tab-pane" id="siegfried<?php echo $num; ?>"><pre style="border: none;">
<?php
							if( isset( $fullsiegfried['files'][0]['matches'][0] ))
								foreach( $fullsiegfried['files'][0]['matches'][0] as $key=>$val ) {
										echo str_pad($key.':', 10).htmlspecialchars( $val )."<br />";
								}
?>
						</pre></div>
						<?php } ?>
						<div class="tab-pane" id="cite<?php echo $num; ?>"><pre>#<?php echo $doc['id']; ?>, <?php echo $mimetype; ?> (<?php echo $doc['file.filemtime']; ?>). <?php echo htmlspecialchars( str_replace( ' ', '+', str_replace( '+', "\\+", $filename ))); ?>, in: <i><?php echo htmlspecialchars( $doc['bestand.name'] ); ?></i>. <?php echo $doc['session.name']; ?>:/<?php echo htmlspecialchars( $path ); ?> [<?php echo implode( '/', $doc['session.group'] ); ?>, <?php echo _format_bytes( $size ); ?>].</pre></div>
<script>
  $(function () {
    //$('#fileTab<?php echo $num; ?> a:first').tab('show')
  })
	$('#fileTab<?php echo $num; ?> a').click(function (e) {
		e.preventDefault();
		$(this).tab('show');
	})
</script>
					</div>
      </div>
    </div>
  </div>
	<?php
	} // foreach( $response->response->docs as $doc )
	?>
	</div>

	<?php
	include( 'paging.inc.php' );

?>
<?php
?>
