=== Propoza ===
Contributors: info.propoza
Donate link: http://example.com/
Tags: comments, spam
Requires at least: 4.0.1
Tested up to: 4.1.1
Stable tag: 1.0.6
Module dependency: WooCommerce
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An awesome plugin that does awesome things

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Propoza'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `propoza-woocommerce.zip.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `propoza-woocommerce.zip.zip`
2. Extract the `propoza` directory to your computer
3. Upload the `propoza` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

= After installation =
1. Configure the plugin by navigating to the settings page on the plugin page or the Propoza tab under WooCommerce settings.
2. If you don't have an account, please press Setup your free account and request a subdomain and API key.
3. Insert your subdomain and API key. These can be found in the registration e-mail or your Propoza dashboard.
4. Press Save changes
5. Press Test connection to test the connection from wordpress to your propoza account.
6. Create a test quote in the front-end.

== Frequently Asked Questions ==

= How do I get an API key? =

Your will receive an API key by mail after you have submitted an account on propoza.com.

= How do I get an Webaddress =

You can submit a subdomain when creating an account at propoza.com.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0.0 =
* First Release

= 1.0.1 =
* Minor textual changes

= 1.0.2 =
* Minor textual and layout changes

= 1.0.3 =
* Changed code to comply with WordPress coding conventions

= 1.0.4 =
* Improved: Security
* Improved: Propoza Dashboard
* Fixed: IE 8 & 9 Bugs
* Fixed: Many small issues

= 1.0.5 =
* Fixed: Quote request showing 0 when not logged in

= 1.0.6 =
* Added: Checkout functionality on accepted quote