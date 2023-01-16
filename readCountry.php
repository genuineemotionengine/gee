<?php
require_once("dbcontroller.php");
$db_handle = new DBController();
if(!empty($_POST["keyword"])) {
$query ="SELECT * FROM app WHERE artist like '" . $_POST["keyword"] . "%' ORDER BY album LIMIT 0,100";
$result = $db_handle->runQuery($query);
if(!empty($result)) {
?>
<ul id="country-list">
<?php
foreach($result as $country) {
?>
<li onClick="selectCountry('<?php echo $country["album"]; ?>');"><?php echo $country["album"]; ?></li>
<?php } ?>
</ul>
<?php } } 
?>
