<nav class="topnav" id="myTopnav">
    <?php foreach ($navigation as $item): ?>
      <?php if (is_array($item['sub'])) { ?>
        <div class="dropdown">
          <button class="dropbtn <?php print $item['class'];?>"><?php print $item['title'];?>
            <i class="fa fa-caret-down"></i>
          </button>
          <div class="dropdown-content">
            <?php foreach ($item['sub'] as $subitem): ?>
              <?php if (is_array($subitem['sub'])) { ?>
                <a href="<?php print $subitem['href'];?>" class="<?php print $subitem['class'];?>" ><?php print $subitem['title'];?></a>
              <?php } ?>
            <?php endforeach; ?>
          </div>
        </div>
    <?php } else { ?>
      <a href="<?php print $item['href'];?>" class="<?php print $item['class'];?>"><?php print $item['title'];?></a>
    <?php } ?>
    <?php endforeach; ?>
    <div class="dropdown float_right">
      <button onclick="location.href='?action=logout';" class="dropbtn ">Logga ut
      </button>
      <!--<a href="?action=logout" class="nav_logout <?php print $item['class'];?>">Logga ut</a>-->
    </div>
    <a href="javascript:void(0);" style="font-size:15px;" class="icon" onclick="myFunction()">&#9776;</a>
</nav>
<br />
<script>
function myFunction() {
  var x = document.getElementById("myTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}
</script>

<style>

#myTopnav {
  width:100%;
}

.nav_logout {
  color: white;
    border: none;
    outline: none;
    padding: 14px 16px;
    background-color: inherit;
    font-family: inherit;
    margin: 0;
    line-break: normal;
    display: inline-block;
    height: 38px;
    font-weight: 600;
    letter-spacing: .1rem;
    white-space: nowrap;
    box-sizing: border-box;
    -webkit-appearance: button;
    overflow: visible;
    /* font: inherit; */
    -webkit-text-size-adjust: 100%;
}

.topnav {
  overflow: hidden;
  background-color: #003366; /*#333;*/
}

.topnav a {
  float: left;
  display: block;
  color: #f2f2f2;
  text-align: center;
  padding: 14px 16px; /*14px 16px; 8px 10px*/
  text-decoration: none;
  font-size: 12px;
}

.active {
  background-color: #04AA6D;
  color: white;
}

.topnav .icon {
  display: none;
}

.nav_logout {
  text-transform: uppercase;
}

.dropdown {
  float: left;
  overflow: hidden;
}

.dropdown .dropbtn {
  font-size: 12px;
  border: none;
  outline: none;
  color: white;
  padding: 14px 16px;
  background-color: inherit;
  font-family: inherit;
  margin: 0;
  line-height: normal;
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: #f9f9f9;
  min-width: 160px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 99;
}

.dropdown-content a {
  float: none;
  color: black;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
  text-align: left;
}

.topnav a:hover, .dropdown:hover .dropbtn {
  background-color: #369; /*#555;*/
  color: white;
}

.dropdown-content a:hover {
  background-color: #ddd;
  color: black;
}

.dropdown:hover .dropdown-content {
  display: block;
}

@media screen and (max-width: 600px) {
  .topnav a:not(:first-child), .dropdown .dropbtn {
    display: none;
  }
  .topnav a.icon {
    float: right;
    display: block;
  }
}

@media screen and (max-width: 600px) {
  .topnav.responsive {position: relative;}
  .topnav.responsive .icon {
    position: absolute;
    right: 0;
    top: 0;
  }
  .topnav.responsive a {
    float: none;
    display: block;
    text-align: left;
  }
  .topnav.responsive .dropdown {float: none;}
  .topnav.responsive .dropdown-content {position: relative;}
  .topnav.responsive .dropdown .dropbtn {
    display: block;
    width: 100%;
    text-align: left;
  }
}
</style>
