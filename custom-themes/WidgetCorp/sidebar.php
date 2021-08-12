
    <aside>

        <?php get_search_form(); ?>
        
        <nav>
            <!-- Add dynamic menu by MH-->
           <?php wp_nav_menu(array('menu' => 'Main Nav Menu')); ?>
        </nav>

        <div class="widget latest-post">

            <h4>Latest Post</h4>
           <!-- To pull content in wordpress we use special funtion called query_posts MH -->
           <!-- showing recent posts MH -->
           <!-- post_per_page=1 return 1 posts per page -->
           <?php query_posts("post_per_page=1"); the_post(); ?> 

            <!-- Static thing MH-->
            <!-- <div class="sidebar-post">
                <p class="date">March 10, 2010</p>
                <h5>This just in: Don't get Gremlins Wet!</h5>
                <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante.</p>
            </div> -->
            <!-- Now change it in wp in dynamic way -->
             <div class="sidebar-post">
                <p class="date"><?php the_date(); ?></p>
                <h5><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
                <p><?php the_excerpt(); ?></p>
            </div>

            <?php wp_reset_query(); ?>

        </div> <!-- END Latest Posts -->

        <div class="widget industry-news">

            <h4>Industry News</h4>

          <!-- Static feed lets make it dynamic MH -->
          <?php if (function_exists('fetch_feed')) { 
                include_once(ABSPATH . WPINC . '/feed.php'); 
                 $feed = fetch_feed('http://feeds2.feedburner.com/lyndablog');
                
                 $limit = $feed->get_item_quantity(2);
                    
                 $items = $feed->get_items(0, $limit);

                if(!$items) {
                    echo "problem";
                } else {
                    // everything's cool
                    foreach ($items as $item) { ?>
                        <div class="sidebar-post">
                            <!--F j, Y that means month day year-->
                            <p class="date"><?php echo $item->get_date('F j, Y'); ?></p>
                            <h5> <a href="<?php echo $item->get_permalink();?>"> <?php echo $item->get_title(); ?> </a></h5>
                            <p><?php echo $item->get_content(); ?></p>
                        </div>
                 <?php }
                }
            } ?>
          <!--   <div class="sidebar-post">
                <p class="date">March 11, 2010</p>
                <h5>Widgets are the new Sprockets</h5>
                <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante.</p>
            </div>

            <div class="sidebar-post">
                <p class="date">March 03, 2010</p>
                <h5>Fifth Birthday of the Intersprockletometer</h5>
                <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper.</p>
            </div> -->

        </div> <!-- END Industry News -->
    <?php if (function_exists('dynamic_sidebar') && dynamic_sidebar('Sidebar Widgets')) : else : ?>
        <!-- All this stuff in here only shows up if you DON'T have any widgets active in this zone -->
    <?php endif; ?>
    </aside>