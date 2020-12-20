<?php
get_header(); ?>

    <div id="main" class = "row">

        <div class="col-2-of-3">
            <?php the_content(); ?>
        </div>

    <div class = "col-1-of-3">
        <?php get_sidebar(); ?>
    </div>

    </div>
<?php
get_footer();