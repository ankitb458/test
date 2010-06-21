<?php get_header(); ?>

<div id="content_wrap">
<div id="content" class="content">
<div id="content_bg">

<?php
if ( have_posts() ) :
while ( have_posts() ) :
the_post();
?>
<div class="entry">
<div class="entry_bg pad">

<h1><?php if ( is_singular() ) : the_title(); else : ?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php endif; ?></h1>

<?php the_content(); ?>

</div>
</div><!-- .entry -->
<?php
endwhile;
else :
?>
<div class="entry">
<div class="entry_bg pad">
404 error
</div>
</div><!-- .entry -->
<?php
endif;
?>

<div class="clear"></div>
</div>
</div>
</div><!-- #content -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>