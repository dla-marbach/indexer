<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="chrome=1">
  <title>Volltext Archiv</title>

  <link href="vendor/bootstrap-3.0.0/dist/css/bootstrap.min.css" rel="stylesheet">  
  
  <link rel="stylesheet" href="lib/css/reset.css" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="lib/css/icons.css" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="lib/css/workspace.css" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="vendor/jquery-ui-1.10.4.custom/css/smoothness/jquery-ui-1.10.4.custom.css" type="text/css" media="screen" charset="utf-8">
 
  <script src="vendor/jquery-ui-1.10.4.custom/js/jquery-1.10.2.js" type="text/javascript" charset="utf-8"></script>
  <script src="vendor/jquery-ui-1.10.4.custom/js/jquery-ui-1.10.4.custom.js" type="text/javascript" charset="utf-8"></script>
	
  <script src="vendor/underscore-1.4.3.js" type="text/javascript" charset="utf-8"></script>
  <script src="vendor/backbone-0.9.10.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/visualsearch.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/views/search_box.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/views/search_facet.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/views/search_input.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/models/search_facets.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/models/search_query.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/utils/backbone_extensions.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/utils/hotkeys.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/utils/jquery_extensions.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/utils/search_parser.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/utils/inflector.js" type="text/javascript" charset="utf-8"></script>
  <script src="lib/js/templates/templates.js" type="text/javascript" charset="utf-8"></script>

  <script src="vendor/bootstrap-3.0.0/dist/js/bootstrap.js" type="text/javascript" charset="utf-8"></script>
  
  <style type="text/css">
	label { display:block; }
	input.text { margin-bottom:12px; width:95%; padding: .4em; }
	fieldset { padding:0; border:0; margin-top:25px; }
  </style>
</head>
<body style="padding-bottom: 70px;">
<!-- body style="background-color: #8f8f8f;" -->

<div id="wrap">

   <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div class="container">
		<h1>Digitaler Nachlass <b>Friedrich A. Kittler</b></h1>
      </div>
    </div>
	<div class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Home</a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li><a href="#about">About</a></li>
          </ul>
          <form style="width: 700px;" class="navbar-form navbar-right">
			<div id="search_box_container"></div>
          </form>
        </div><!--/.navbar-collapse -->
      </div>
    </div>
  <div class="container">
	<!-- <div id="search_query">&nbsp;</div> -->
    <script type="text/javascript" charset="utf-8">
	function setKeywords(signature)
	{
		
	}
	
	function execSearch( page )
	{
		$("body").css( 'cursor', 'wait' );
		var docHeight = $(document).height();

		   $("#overlay")
			  .height(docHeight)
			  .css({
				 'opacity' : 0.4,
				 'position': 'absolute',
				 'top': 0,
				 'left': 0,
				 'background-color': 'black',
				 'width': '100%',
				 'z-index': 5000
			  });
			  $("#overlay").show();
	  
	     facets = window.visualSearch.searchQuery.facets();
		 query = window.visualSearch.searchQuery.serialize();
		 query_plain = $("#query_plain").val();
		 do_plain = $("#do_plain").val();
		 window.location.hash = '#'+encodeURIComponent( query );
		 $.ajax({
				  type: "POST",
				  url: "result.php",
				  data: { page: page, limit: 10, query: facets, query_plain: query_plain, do_plain: do_plain },
				  dataType: "html"
				}).done(function( msg ) {
				  var $content = $('#content');
				  $content.html( msg );
				  $("body").css( 'cursor', 'default' );
		  		  $("#overlay").hide();

				});
		$("html, body").animate({ scrollTop: 0 }, "slow");
	}

	function newquery( qstr ) 
	{
		window.visualSearch.searchBox.setQuery( qstr );
		window.visualSearch.searchBox.searchEvent( $('#search_box_container') );
		$("html, body").animate({ scrollTop: 0 }, "slow");
	}
	
	function updateStatus( id, status )
	{
		
		$("body").css( 'cursor', 'wait' );
		$.ajax({
					  type: "POST",
					  url: "setstatus.php",
					  data: { id: id, status: status },
					  dataType: "html"
					}).done(function( msg ) {
					  var $content = $('#ampel'+id.replace( '.', '' ));
					  $content.html( msg );
					  $("body").css( 'cursor', 'default' );
					  //$("#overlay").hide();
					});
	}
	
      $(document).ready(function() {
		query = decodeURIComponent( window.location.hash.slice(1));
        window.visualSearch = VS.init({
          container  : $('#search_box_container'),
          // query      : 'text: test',
		  remainder : 'text',
          query      : query,
		  showFacets : true,
          unquotable : [
            'group',
            'mime',
			'type',
            'text',
            'ext',
            'name',
			'session',
			'id',
			'checksum',
			'path',
			'tikainfo',
			'size',
			'mtime',
			'ctime',
			'atime',
			'nsrl',
			'nsrl_apptype',
			'locked',
			'status',
			'unknown',
          ],
		  placeholder : "Search for your documents...",
          callbacks  : {
            search : function(query, searchCollection) {
			  $("#do_plain").val( 0 );
			  execSearch( 1 );

              var $query = $('#search_query');
              $query.stop().animate({opacity : 1}, {duration: 300, queue: false});
              $query.html('<span class="raquo">&raquo;</span> You searched for: <b>' + searchCollection.serialize() + '</b>');
              clearTimeout(window.queryHideDelay);
              window.queryHideDelay = setTimeout(function() {
                $query.animate({
                  opacity : 0
                }, {
                  duration: 1000,
                  queue: false
                });
              }, 2000);

		  },
            valueMatches : function(category, searchTerm, callback) {
              switch (category) {
			  case 'group':
			  case 'mime':
			  case 'type':
			  case 'text':
			  case 'ext':
			  case 'name':
			  case 'session':
			  case 'path':
			  case 'nsrl':
			  case 'nsrl_apptype':
			  case 'locked':
			  case 'status':
				 facets = window.visualSearch.searchQuery.facets();
			     $.ajax({
					  type: "POST",
					  url: "autocomplete.php",
					  data: { category: category, searchTerm: searchTerm, limit: -1, query: facets }
					}).done(function( msg ) {
					  callback( msg );
					});
				break;
              }
            },
            facetMatches : function(callback) {
              callback([
                'group', 
				'mime', 
				'type', 
				'text', 
				'ext', 
				'name', 
				'session', 
				'id', 
				'checksum',
				'path', 
				'tikainfo', 
				'size', 
				'mtime',
				'ctime',
				'atime',
				'nsrl',
				'nsrl_apptype',
				'locked',
				'status'
//                { label: 'city',    category: 'location' },
              ]);
            }
          }
        });
		$("#do_plain").val( 0 );
		execSearch( 1 );
      });
	  
	  
    </script>
<div id="content">

</div>
<nav class="navbar navbar-default navbar-fixed-bottom">
      <div class="container">
	  <img style="text-align: center; height: 65px;" src="logo.png" />
      </div>
</nav>

</div>

<div id='overlay'></div>
<div id="dialog-form" title="Create new user">
	<p class="validateTips">All form fields are required.</p>
	<form>
		<fieldset>
			<label for="name">Name</label>
			<input type="text" name="name" id="name" class="text ui-widget-content ui-corner-all">
			<label for="email">Email</label>
			<input type="text" name="email" id="email" value="" class="text ui-widget-content ui-corner-all">
			<label for="password">Password</label>
			<input type="password" name="password" id="password" value="" class="text ui-widget-content ui-corner-all">
		</fieldset>
	</form>
</div>
<button id="create-user">Create new user</button>

</body>
</html>
