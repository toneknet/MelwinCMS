<!-- nav_3 -->
<nav>
	<ul class="navbar cf">
    <?php foreach ($navigation as $item): ?>
      <li><a href="<?php print $item['href'];?>" class="<?php print $item['class'];?>" title=""><?php print $item['title'];?></a>
<?php if (is_array($item['sub'])) { ?>
        <ul>
        <?php foreach ($item['sub'] as $subitem): ?>
          <li><a href="<?php print $subitem['href'];?>" class="<?php print $subitem['class'];?>" title=""><?php print $subitem['title'];?></a>
          <?php if (is_array($subitem['sub'])) { ?>
            <ul>
              <?php foreach ($subitem['sub'] as $subitems): ?>
              <li><a href="<?php print $subitems['href'];?>" class="<?php print $subitems['class'];?>" title=""><?php print $subitems['title'];?></a></li>
              <?php endforeach; ?>
            </ul>
            <?php } ?>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php } ?>
      </li>
    <?php endforeach; ?>
    </ul>
</nav>
</div>
