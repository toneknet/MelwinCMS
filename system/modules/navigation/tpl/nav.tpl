<div id="navcontainer">
    <ul class="navigation">
    <?php foreach ($navigation as $item): ?>
        <li class="<?php print $item['class'];?>"><a href="<?php print $item['href'];?>" class="<?php print $item['class'];?>" title=""><?php print $item['title'];?></a>
        <?php if (is_array($item['sub'])) { ?>	
            <!--<li class="" id="">-->
                <ul class="">
                <?php foreach ($item['sub'] as $subitem): ?>
                    <li><a href="<?php print $subitem['href'];?>" class="<?php print $subitem['class'];?>" title=""><?php print $subitem['title'];?></a></li>
                <?php endforeach; ?>
                </ul>
            <!--</li>-->
        <?php } ?>
        </li>
    <?php endforeach; ?>        
    </ul>
</div>