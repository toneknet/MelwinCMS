<div id="navcontainer">
<ul class="navigation">
    <?php
    foreach ($navigation as $value) {
        if (!is_array($value)) {
        ?>
            <li><a href="<?=$value['src'];?>"><?=$value['label'];?></a></li>
        <?php
        } else {
            foreach ($value as $subvalue) {
            ?>
                <ul>
                    <li><a href="<?=$subvalue['src'];?>"><?=$subvalue['label'];?></a></li>
                </ul>
            <?php
            }
        }
    }
    ?>
</ul>
    </div>