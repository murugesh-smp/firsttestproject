<?php
$this->start('sidebar');
// block code...?>
<ul>
      <li><a href="http://www.Google.com" target=_blank >Google</a></li>
      <li><a href="">this is another Link</a></li>
      <li><a href="">this is also a test link</a></li>
   </ul> 
  
<?php
$this->end();

echo  $this->fetch('sidebar');
echo 'asdf';
?>