<?php

/*
Plugin Name: Tweet Watcher
Plugin URI: https://github.com/simonwheatley/Tweet-Watcher/
Description: Authenticates with a number of Twitter accounts and watches their mention stream, firing actions for each mention.
Version: 0.2
Author: Simon Wheatley
Author URI: http://codeforthepeople.com/
*/
 
/*  Copyright 2012 Code for the People Ltd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * Main plugin information and requires.
 *
 * @package Twitter Watcher
 * @since 0.1
 * @copyright Copyright (C) Code for the People Ltd
 */

require_once( 'class-plugin.php' );
require_once( 'class-tweet-watcher.php' );

function cftp_tweet_watcher_file() {
	return __FILE__;
}

?>