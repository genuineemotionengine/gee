<?php

for ($x = 1; $x <= 5; $x++) {
    
echo "$('#playpause$x').removeClass('bi-chevron-right').addClass('bi-pause');\n";
echo "$('#imagepad$x').removeClass('imgpad').addClass('noimgpad');\n";

}
