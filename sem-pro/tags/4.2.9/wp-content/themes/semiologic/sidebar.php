<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# The Semiologic theme features a custom.php feature. This allows to hook into
# the template without editing its php files. That way, you won't need to worry
# about losing your changes when you upgrade your site.
#
# You'll find detailed sample files in the custom-samples folder
#
?>

<div id="sidebar" class="sidebar">
<div class="pad">
<ul>
<?php
if ( function_exists('dynamic_sidebar') )
{
	#echo '<li style="background-color: yellow;">';
	#var_dump($setup);
	#echo '<li>';

	dynamic_sidebar($sidebar_id);
}
else  # no widgets
{

if ( function_exists('the_sidebar_tile') ) :
?>
	<li>
		<div class="widget">
		<?php the_sidebar_tile(); ?>
		</div>
	</li>
<?php
endif;


if ( function_exists('the_sidebar_ad') && $GLOBALS['sem_ad_space']->ad_distribution['sidebar'] ) :
?>
	<li>
		<div class="widget">
		<?php the_sidebar_ad(); ?>
		</div>
	</li>
<?php
endif;


if ( function_exists('jal_democracy') ) :
?>
	<li>
		<div class="widget">
		<h2>Poll</h2>
		<?php jal_democracy(); ?>
		</div>
	</li>
<?php
endif;

} # end no widgets
?>
</ul>
</div>
</div><!-- #sidebar -->