<div style="vertical-align: top;">
    <ul class="pagination">
<?php	
	if( $currPage <= 1 )
	{
		echo <<<EOT
		<li class="disabled"><a href="#">Prev</a></li>
EOT;
	}
	else
	{
		$p = $currPage-1;
		echo <<<EOT
		<li><a href="javascript:execSearch( {$p} );">Prev</a></li>
EOT;
	}
	if( $currPage-2 > 1 ) 
	{
		echo <<<EOT
		<li><a href="javascript:execSearch( 1 );">1</a></li>
EOT;
	}
	if( $currPage-3 > 1 ) 
	{
		echo <<<EOT
		<li class="disabled"><a href="#">...</a></li>
EOT;
	}
	for( $i = max( $currPage-2, 1 ); $i <= min( $currPage+2, $maxPage ); $i++ )
	{
		$class = ($i == $currPage?'class="active"':'');
		echo <<<EOT
		<li {$class}><a href="javascript:execSearch( {$i} );">{$i}</a></li>
EOT;
	}
	if( $currPage+3 < $maxPage ) 
	{
		echo <<<EOT
		<li class="disabled"><a href="#">...</a></li>
EOT;
	}
	if( $currPage+2 < $maxPage ) 
	{
		echo <<<EOT
		<li><a href="javascript:execSearch( {$maxPage} );">{$maxPage}</a></li>
EOT;
	} 
	if( $currPage >= $maxPage )
	{
		echo <<<EOT
		<li class="disabled"><a href="#">Next</a></li>
EOT;
	}
	else
	{
		$p = $currPage+1;
		echo <<<EOT
		<li><a href="javascript:execSearch( {$p} );">Next</a></li>
EOT;
	}
?>	
    <!-- /ul -->
      <li class="active">
        <input style="text-align: right; vertical-align: top; width: 60px; height: 34px; border: 1px solid rgb(221, 221, 221); margin-left: 4px; " type="text" id="page" size="7" maxlength="7" value="<?php echo $currPage; ?>" />
      </li>
      <li class="active">
        <button class="btn btn-small" style="vertical-align: top;"  onClick="execSearch( $('#page').val() );">Page</button>
      </li>
    </ul>
	
</div>
