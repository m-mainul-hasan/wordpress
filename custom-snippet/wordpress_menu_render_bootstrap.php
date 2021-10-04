<!-- Rendering wordpress menu through custom front end code -->
<?php $menuitems = wp_get_nav_menu_items(2); // menu id?>
<nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <?php

            $count = 0;
            $submenu = false;

            foreach ($menuitems as $item) {
                if (!$item->menu_item_parent) {
                    $parent_id = $item->ID;
                }
                ?>
                <?php if (!$item->menu_item_parent) { ?>
                    <li class="nav-item <?php if ($menuitems[$count + 1]->menu_item_parent == $item->ID) echo 'dropdown';?>">
                <?php } ?>
                <?php if ($menuitems[$count + 1]->menu_item_parent == $item->ID) { ?>
                    <a class="nav-link dropdown-toggle <?php echo ($item->object_id == $post->ID) ? 'active' : ''?>" href="#" id="navbarDropdown" role="button"
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo $item->title; ?>
                    </a>
                <?php } else { ?>
                    <?php if (!$item->menu_item_parent) { ?>
                        <a class="nav-link <?php echo ($item->object_id == $post->ID) ? 'active' : ''?>" href="<?php echo $item->url; ?>"><?php echo $item->title; ?></a>
                    <?php } ?>
                <?php } ?>

                <?php if ($parent_id == $item->menu_item_parent) { ?>
                    <?php if (!$submenu) {
                        $submenu = true; ?>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <?php } ?>
                    <a class="dropdown-item" href="<?php echo $item->url; ?>"><?php echo $item->title; ?></a>
                    <?php if ($menuitems[$count + 1]->menu_item_parent != $parent_id && $submenu) { ?>
                        </div>
                        <?php $submenu = false;
                    }
                } ?>
                <?php if ($menuitems[$count + 1]->menu_item_parent != $parent_id) { ?>
                    </li>
                    <?php $submenu = false;
                } ?>
                <?php $count++;
            } ?>
        </ul>
    </div>
</nav>