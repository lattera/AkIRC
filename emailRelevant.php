<?php

// relevant.log
$date = date("M-d-y");

rename( "relevant.log", "relevant/$date.log" );
touch( "relevant.log" );

mail( "jason@jason-rush.com", "Lethe - Relevant Log", file_get_contents( "relevant/$date.log") );
