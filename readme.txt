=== Perfect Paper Passwords ===
Contributors: Henrik.Schack
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=CA36JVKMLE9EA&lc=DK&item_number=Perfect%20Paper%20Passwords%20plugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: authentication,otp,ppp,password,security,login
Requires at least: 3.1.3
Tested up to: 3.1.3
Stable tag: 0.52

Perfect Paper Passwords for your Wordpress blog.

== Description ==

The Perfect Paper Passwords plugin for WordPress gives you free multifactor authentication for your WordPress blog.

The multifactor authentication requirement can be enabled on a per user basis, You could enable it for your administrator account, but login as usual with less privileged accounts.

The Perfect Paper Passwords system itself was created by Steve Gibson, https://www.grc.com/ppp

**This plugin requires the SHA256 hashing algorithm, the BCMath library and Mcrypt/AES-128 (Rijndael-128) to be available in your PHP installation, it's not possible to activate the plugin without**

== Installation ==

1. Install and activate the plugin.
2. Enter a secret on the Users -> Profile and Personal options page, in the Perfect Paper Passwords section.
3. After saving your changes, copy the Sequence key, and goto https://www.grc.com/ppp and create yourself a few passcards.
4. That's it, you are ready to login with Perfect Paper Passwords on your Wordpress blog.

== Frequently Asked Questions ==

= Are there any special requirements for my Wordpress/PHP installation ? =

Yes, your PHP installation needs the SHA256 hashing algorithm, BCMath library and Mcrypt/AES-128 (Rijndael-128)

= Can I use Perfect Paper Passwords with the Android/iPhone apps for Wordpress ? =

No, that wont work, but you could create a special account for mobile usage and choose not to enable 
Perfect Paper Passwords for this account.

= Oops, I lost my passcards, as well as my secret/sequencekey, can't get access to my Wordpress blog, what to do now ? =

You'll have to somehow delete the plugin from your Wordpress installation, using cPanel, FTP or SSH you
can delete the /wp-content/plugins/perfect-paper-passwords directory.

= Perfect Paper Passwords, how secure is this, how does it work ? =

Steve Gibson (the creator) explains it very well here : http://www.grc.com/ppp/design.htm and http://www.grc.com/ppp/algorithm.htm

== Screenshots ==

1. Perfect Paper Passwords section on the Profile and Personal options page.
2. The enhanced loginbox.
3. Passcard generation at https://www.grc.com/ppp
4. Passcards containing Perfect Paper Passwords

== Changelog ==

= 0.52 =
Bugfix: Another single/double quote problem fixed.

= 0.51 =
Bugfix: Secrets with single or double quotes didn't work.

= 0.50 =
Userspecific passcode length, anything from 2 to 16 characters can be used.

Italian translation by Aldo Latino

HTML cleanup (Validation errors)

Algorithm used to show code/cardnumbers to unknown users changed, 
should be a bit harder to guess valid usernames now.

Bugfixes 

= 0.44 =
Trying to clean up svn mess after screenshot rename operation

= 0.43 =
Screenshot files renamed.

= 0.42 =
Version bumb to fix svn problem

= 0.41 =
Nicer looking plugin name on the wordpress.org plugin site

= 0.40 =
* Initial release

== Upgrade Notice ==

= 0.5 =
Upgrade is recommended if you need passwords longer than 4 characters.


== Translations ==

Localization files must be named like this:

perfectpaperpasswords-it_IT.mo (Italian binary file)

perfectpaperpasswords-it_IT.po (Italian source file)

in order to be recognized by my plugin.


== Credits ==

Thanks to:

[Aldo Latino](http://profiles.wordpress.org/users/aldolat/) for his help, suggestions and Italian translation.


