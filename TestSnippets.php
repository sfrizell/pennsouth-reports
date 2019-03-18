<?php
/**
 * TestSnippets.php
 * User: sfrizell
 * Date: 5/25/17
 *  Function:
 */

if ( (!strpos('blah, blah External Move', 'External Move') == true)  ) {
    print "\n true \n";
}
else {
    print"\n false \n";
}

print (!strpos(' blah External Move bbb', 'External Move'));