<!DOCTYPE html>
<html <?php language_attributes()?>>
	<head>
		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type')?>; charset=<?php bloginfo('charset')?>">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		<link rel="pingback" href="<?php bloginfo('pingback_url')?>">
		<title><?php wp_title('|', true, 'right')?><?php echo get_bloginfo( 'name' ); ?></title>
		<?php wp_head()?>
		<link rel="stylesheet" href="<?php echo get_stylesheet_uri()?>">
		<!--[if lt IE 9]>
			<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
			<script src="//cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.js"></script>
		<![endif]-->
	</head>
	<body <?php body_class()?>>
		<div class="container header">
			<div class="row">
				<div class="col-sm-6 title">
					<a href="http://www.nyintergroup.org/"><img src="<?php echo get_stylesheet_directory_uri()?>/img/title.png" width="289" height="91" class="img-responsive"></a>
				</div>
				<div class="col-sm-6 hidden-xs liberty">
					<img src="<?php echo get_stylesheet_directory_uri()?>/img/liberty.png" width="163" height="91" class="img-responsive">
				</div>
			</div>
		</div>