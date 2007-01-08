<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<?php echo include_http_metas() ?>
<?php echo include_metas() ?>
<?php echo include_title() ?>

<link rel="shortcut icon" href="/favicon.ico" />

<!--[if lt IE 7.]>
<?php echo stylesheet_tag('/sf/sf_default/css/ie.css') ?>
<![endif]-->

</head>
<body>
<div class="sfTContainer">
  <?php echo link_to(image_tag('/sf/sf_default/images/sfTLogo.png', array('alt' => 'symfony PHP Framework', 'class' => 'sfTLogo', 'size' => '186x39')), 'http://www.symfony-project.com/') ?>
  <?php echo $sf_data->getRaw('sf_content') ?>
</div>
</body>
</html>
