<div>
<?php foreach ($navigation as $item): ?>        
      <strong><a href="<?php print $item['href'];?>" class="<?php print $item['class'];?>" title=""><?php print $item['title'];?></a></strong><br>
<?php if (is_array($item['sub'])) { ?>
        <?php foreach ($item['sub'] as $subitem): ?>
          <a href="<?php print $subitem['href'];?>" class="<?php print $subitem['class'];?>" title=""><?php print $subitem['title'];?></a> | 
          <?php if (is_array($subitem['sub'])) { ?>	
              <?php foreach ($subitem['sub'] as $subitems): ?>
              <a href="<?php print $subitems['href'];?>" class="<?php print $subitems['class'];?>" style="font-style: italic;" title=""><?php print $subitems['title'];?></a> |
              <?php endforeach; ?>
            <?php } ?>
        <?php endforeach; ?>
      <?php } ?>
      <br>
    <?php endforeach; ?>      
</div>