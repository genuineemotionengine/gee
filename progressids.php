<?php

for ($x = 1; $x <= 4; $x++) {

echo "$('#dynamic$x').css('width', currentprogress + '%');\n";
echo "$('#secondscur$x').html(pad(current%60));\n";
echo "$('#minutescur$x').html(pad(parseInt(current/60,10)));\n";

}