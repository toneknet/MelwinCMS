<div id="navcontainer">
<ul class="navigation">
    <?php
    foreach ($navigation as $value) {

        ?>
            <li><a href="<?=$value['src'];?>"><?=$value['label'];?></a></li>
        <?php
    }
    ?>
</ul>
    </div>