=== Tweet Images ===
Contributors: simonwheatley
Donate link: http://www.simonwheatley.co.uk/wordpress/
Tags: twitter, tweet, image, hosting, twitter image hosting, tweetie, twitter for iphone, twitpic, yfrog, twitterific
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.9
 
A replacement for TwitPic, yFrog, etc, for posting images from Twitter clients to your blog. Creates a post per image.

== Description ==

A replacement for TwitPic, yFrog, etc, for hosting your tweeted images on your WordPress blog - as used by Stephen Fry for uploading his Twitter images to his blog! Creates a post per image. I've got instructions for Twitter for iPhone, and for Twitterific for iPhone/iPad. 

Each post created by this plugin contains the image (resized) and the text of the tweet (with hashtags linked to Twitter Search, and URLs linked)

Hashtags from the tweet are converted into WordPress tags. Posts are set to the "Image" post format (requires WP 3.1+).

Includes the option to set a specific Bit.ly account for shortening the URLs of the Tweet Image posts. So if you want to have your tweeted pics on a specific and separate short URL domain from tweeted links, you can do.

Read the Installation instructions for how to set this up on your handheld telephone.

Developers! Developers! Developers! There's lots of handy filters and actions for you to extend this plugin, use a custom post type rather than regular ol' posts, customise the post content, and lots, lots more!

Translators: Check out the file in the locale folder, translations welcome! Thanks!

== Installation ==

= WordPress plugin installation =

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Make sure you set the Admin > Settings > Permalinks to something other than "default"

Optionally: install [WP Touch](http://wordpress.org/extend/plugins/wptouch/), free, or [WP Touch Pro](http://www.bravenewcode.com/products/wptouch-pro/), paid, or a similar WordPress mobile theme to ensure a nice mobile friendly layout for your images.

Instructions below for a couple of Twitter apps I've tested this on. There may be other apps this works with, on platforms I'm not familiar with (e.g. Android, WebOS, etc), feel free to Give It A Go™ and [let me know](http://www.simonwheatley.co.uk/contact-me/) how you get on, please!

= Settings for Twitter for iPhone =

1. Launch Twitter for iPhone
1. Tap on the three dots bottom right ("•••")
1. Tap on "Accounts & Settings"
1. Tap on "Settings" (bottom left)
1. Tap on "Services" (you might need to swipe down the screen to find it)
1. Tap on "Image Service"
1. Tap on "Custom…"
1. Still with me?
1. Clear any value in the form field, and copy/paste the value from your "Endpoint" field in your WordPress user profile into the "Image Service API Endpoint" field... you might find it easier to copy the text into an email on your iPhone, copy it from the iPhone Mail app and paste into the field (here's [a cheesy man showing you how to copy/paste](http://www.youtube.com/watch?v=pktF_z-Hj5A))
1. Tap "Save"
1. You're done… Phew!

= Settings for Twitterific on iPhone/iPad =

1. Launch Twitterific
2. Go to create a new Tweet
3. Tap on the Camera icon on the right
4. Choose "Change Upload Service"
5. Tap "Other" at the bottom
6. Nearly there…
7. Clear any value in the form field, and copy/paste the value from your "Endpoint" field in your WordPress user profile into the "Image Service API Endpoint" field... you might find it easier to copy the text into an email on your iPhone, copy it from the iPhone Mail app and paste into the field (here's [a cheesy man showing you how to copy/paste](http://www.youtube.com/watch?v=pktF_z-Hj5A))
8. Tap the "Upload Service" back button top left
9. Tap "Done" top right
10. Yep, you're done now… tweet away!

= Settings for Tweetbot for iPhone =

1. Launch Tweetbot for iPhone
1. Navigate to the "Accounts" screen (keep tapping top left button), if you aren't already there
1. Tap on the "Settings" button
1. Under "Account Settings", tap on the account you want to set up
1. Tap on "Image Upload"
1. Tap on "Custom" (towards the bottom of the list)
1. Clear any value in the form field, and copy/paste the value from your "Endpoint" field in your WordPress user profile into the "Image Service API Endpoint" field... you might find it easier to copy the text into an email on your iPhone, copy it from the iPhone Mail app and paste into the field (here's [a cheesy man showing you how to copy/paste](http://www.youtube.com/watch?v=pktF_z-Hj5A))
1. Tap "Done"
1. Tap the top left back button a few times to get back to the "Accounts" screen
1. You're done… Phew!

== Frequently Asked Questions ==

= What's this about a secret? (AKA "The Security Question") =

Unless your website is secure, i.e. the web address starts with "https" and you see a little padlock in the location bar when you're visiting the site, then all the information between your iPhone and your website will be transmitted "in the clear". Transmitting information in the clear means that if someone can intercept the information then they can see it without any further effort on their part. This means that transmitting your WordPress or Twitter username and password whenever you send an image opens you up to potential hackers and mischief-makers stealing your credentials and posting information on your WordPress blog or Twitter account. To get around this, Tweet Images generates you a unique and very hard to guess secret. The Tweet Images secret can only be used to post images to your WordPress site, and can be reset very easily to re-secure your site. This isn't an ideal solution, but it's the best we could think of in the circumstances.

Future releases of this plugin will support secure connections between your iPhone and website.

= So how do I reset my secret? =

Go to your User Profile (in the WordPress admin area, click on your username top right) and click "Regenerate Secret". You'll then need to redo the settings in your Twitter app.

= Does this plugin have any hooks? =

Yes it does! Check out the code for more, but briefly you can filter the category a tweet image post is put in with the `wpti_post_category`, the post data used for the post with the `wpti_post_data` filter, the image size used for the tweet image in the post with the `wpti_image_size` filter, and the tags for the post with the `wpti_post_tag` filter. There is also an action `wpti_published_post` which is fired whenever a tweet image post is published.

== Upgrade Notice ==

= 0.9 =

Should reduce the load on your server a bit.

= 0.8 =

Fixes posting from Tweetbot, thanks to Steve Bullen for identifying the issue and [providing a fix](https://twitter.com/stevenbullen/status/79161817829613568)

== Changelog ==

= 0.9 =

* BUGFIX: Stop refreshing rewrite rules on every request

= 0.8 =

* BUGFIX: Fixes posting from Tweetbot (which didn't like the XML declaration for some reason… go figure)

= 0.71 =

* CLARIFICATION: Places a notice in the admin area if you don't have pretty permalinks enabled.

= 0.7 =

* BUGFIX: App started throwing "Error: Could not post image" message. (Now using response XML containing pretty much *only* the MEDIAURL element; i.e. no longer using a response as close to the TwitPic API v2 Upload method as I can get.)
* New filter on post data before inserting it
* New filter on tag taxonomy name before setting the terms
* Changed filter name to wpti_update_post_data from wpti_post_data

= 0.6 =

* Fix security hole whereby the "allow uploaded tweet images from this user" option wasn't being respected
* Set the author ID properly
* Inherit the post_status for the attachment from the post
* Add a filter to allow alteration of the Tweet Image post content
* Link to fullsize image
* Flag to try and get around a freaky race condition
* Posts definitely initially created in draft status
* Added a test script

= 0.5 =

* Allow the admin to specify which category the Tweet Image is posted to by simply checking a box

= 0.4 =

* Version bump
* Don't show on user profile unless this user can publish posts
* Use home_url for the API endpoint, not site_url
* More consistent text in admin notice
* New translations file

= 0.3 =

* Now you can set a specific Bit.ly account to shorten the links to your Tweet Image posts
* Sets the post format to "image"

= 0.2 =

* Added some security
* Better installation instructions
* Check more things before uploading an image

= 0.1 =

* The initial release
