=== Tweet Watcher ===
Contributors: simonwheatley, cftp
Tags: twitter, tweet
Requires at least: 3.4.2
Tested up to: 3.4.2
Stable tag: 0.5
 
A WordPress plugin which authenticates with a number of Twitter accounts and watches their mention and tweet stream, firing actions for each mention or tweet.

== Description ==

Authenticates with a number of Twitter accounts and fires actions for each mention or tweet.

This plugin will do NOTHING unless hook the actions, you can think of it as infrastructure to build upon.


== Changelog ==

= 0.5 =

Wednesday 5 December 2012

* BUGFIX: Removed some PHP notices from settings page for unset indexes on the $user array
* BUGFIX: Remove stray error_log call.

= 0.4 =

Tuesday 25 September 2012

* ENHANCEMENT: Allow admin to set different last tweet and last mention IDs.
* ENHANCEMENT: Require POST request to unauthenticate user.
* ENHANCEMENT: Introduce sanitise_id_str method to sanitise Twitter IDs while avoiding casting to integers (which corrupts data on 32 bit systems).
* BUGFIX: Remove stray var_dump.

= 0.3 =

Friday 21 September 2012

* BUGFIX: Remove test hook.
* ENHANCEMENT: Get 100 tweets in a request.

= 0.2 =

* ENHANCEMENT: Allow authorisation for multiple Twitter accounts.
* CHANGED: The arguments passed to the `twtwchr_mention` action.

= 0.1 =

* Initial release!
