<?php
$files = array_diff(scandir(__DIR__), array('..', '.','index.php','geticons.php'));


print "<table style='table-layout: fixed;'>";
print "<tr>
        <th>Icon</th>
        <th>Kortnamn</th>
        <th>Sökväg</th>
      </tr>";
foreach ($files as $key => $value) {
  print "<tr>
          <td><img src='{$value}' /></td>
          <td>" . str_replace(array('.png','.gif','.jpg'),array('','',''), $value) . "</td>
          <td>{$value}</td>
        </tr>";
}


print "</table>";

/*
print "<pre>";
print_r($files);
print "</pre>";
*/
