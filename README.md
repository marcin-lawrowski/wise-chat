
# Wise Chat plugin for WordPress

**Wise Chat** is an advanced chat plugin that helps to build social networks. It can significantly increase user engagement on your website by adding chat rooms that allow to exchange **real time messages**. The plugin is very easy to setup and configure.  It also has a growing list of features and responsive support.

**Main features:**
  - No external server required
  - Unlimited chat channels
  - Single sign-on
  - Flood control
  - Access restriction
  - Posting links and images
  - File attachments
  - Notifications
  - Localization
  - Appearance adjustments
  - Chat moderation features
  - Messages filtering
  - Bans and kicks
  - Emoticons
  - [See the full list of features](https://wordpress.org/plugins/wise-chat/#description)

### Tech
   
* [PHP](http://www.php.net/)
* [WordPress](https://wordpress.org/)
* [jQuery](https://jquery.com/)


### Installation

#### Requirements:

 - WordPress instance
 - Admin access to the WordPress instance


#### Steps:

 1. Clone the repository:
```sh
$ git clone https://github.com/marcin-lawrowski/wise-chat.git
```
 2. Upload the entire wise-chat folder to the plugins directory (usually `/wp-content/plugins/`) of WordPress instance.
 3. Log in as an administrator to WordPress and activate Wise Chat plugin through the "Plugins" menu.
 4. Place  `[wise-chat]` shortcode in your post (or page) and visit the post (or page).
 5. Alternatively install it in your template using `<?php if (function_exists('wise_chat')) { wise_chat(); } ?>` code.
 6. Alternatively install it using a widget in "Appearance → Widgets", it's called Wise Chat Window.

#### Post Installation Notices:

 - Go to "Settings → Wise Chat Settings" page and adjust all the settings according to your needs. 
 - Go to "Settings → Wise Chat Settings" page, select Localization tab and translate all the messages into your own language.
 - Posting pictures from camera / local storage is limited to the specific range of Web browsers: IE 10+,
   Firefox 31+, Chrome 31+, Safari 7+, Opera 27+, iOS Safari 7.1+, Android Browser 4.1+, Chrome For Android 41+.

#### Documentation:
Check the full documentation [here](https://kaine.pl/projects/wp-plugins/wise-chat/documentation/).

License
----

GPL-2.0


**Free Software, Hell Yeah!**