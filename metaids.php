<?php

for ($x = 1; $x <= 5; $x++) {

echo "$('#image".$x."').attr('src',result.image);\n";
echo "$('#title".$x."').text(result.title);\n";
echo "$('#artist".$x."').text(result.artist);\n";
echo "$('#album".$x."').text(result.album);\n";
echo "$('#albumartist".$x."').text(result.albumartist);\n";
echo "$('#secondsdur".$x."').html(pad(result.duration%60));\n";
echo "$('#minutesdur".$x."').html(pad(parseInt(result.duration/60,10)));\n";
echo "$('#secondscur".$x."').html(pad(current%60));\n";
echo "$('#minutescur".$x."').html(pad(parseInt(current/60,10)));\n";

}