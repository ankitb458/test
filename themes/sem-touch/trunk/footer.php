<div class="clear"></div>
</div>
</div><!-- #body -->


<div id="footer_boxes" class="footer_boxes">
<div id="footer_boxes_bg">

<?php dynamic_sidebar('footer_boxes'); ?>

<div class="clear"></div>
</div>
</div><!-- #footer_boxes -->


<div id="footer_nav" class="footer_nav navbar">
<div id="footer_nav_bg" class="pad">

<?php wp_nav_menu(array(
	'theme_location' => 'footer',
	'container' => false,
	'depth' => 1,
	)); ?>

<div class="clear"></div>
</div>
</div><!-- #footer_nav -->


<div id="footer" class="footer">
<div id="footer_bg" class="pad">

Lorem ipsum dolor sit amet, consectetur adipiscing elit.

<div class="clear"></div>
</div>
</div><!-- #footer -->


<div class="clear"></div>
</div>
</div><!-- #wrap -->

<?php wp_footer(); ?>
</body>
</html>