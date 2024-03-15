=== Backuply - Backup, Restore, Migrate and Clone ===
Contributors: softaculous, backuply
Tags: backup, restore, database backup, cloud backup, wordpress backup, migration, cloning, backuply, local backup, amazon s3, database, google drive, gdrive, dropbox, FTP, SCP, SFTP, onedrive, WebDAV
Requires at least: 4.7
Tested up to: 6.4
Requires PHP: 5.5
Stable tag: 1.2.2
License: LGPL v2.1
License URI: http://www.gnu.org/licenses/lgpl-2.1.html

Backup, restores, and migration with Backuply are fairly simple with a wide range of storage options from Local Backups, FTP to cloud options like AWS S3, Dropbox, Google Drive, SFTP, FTPS, WebDav.

== Description ==

Backuply is a WordPress backup plugin that helps you backup your WordPress website, saving you from loss of data because of server crashes, hacks, dodgy updates, or bad plugins.

Backuply comes with Local Backups and Secure Cloud backups with easy integrations with FTP, FTPS, SFTP, WebDAV, Google Drive, Microsoft OneDrive, Dropbox, Amazon S3 and easy One-click restoration.

Your website is your asset and it needs to constantly be protected from various security issues, server issues, hacking, etc. While you take all precautionary steps to protect your website, backups are the best form of security. With Backuply, you can be confident that your data is protected and is always available for restore during any disaster. Backuply creates full backups of your website and you can restore it to the same or a new WordPress website with the click of a button.

Our backup and website cloning technology have been in use for more than a decade and we have now ported it to WordPress.

You can find our official documentation at [https://backuply.com/docs](https://backuply.com/docs). We are also active in our community support forums on wordpress.org if you are one of our free users. Our Premium Support Ticket System is at [https://softaculous.deskuss.com/open.php?topicId=17](https://softaculous.deskuss.com/open.php?topicId=17)

[Home Page](https://backuply.com "Backuply Homepage") | [Support](https://softaculous.deskuss.com/open.php?topicId=17 "Backuply Support") | [Documents](http://backuply.com/docs "Documents")

== Features ==
* **Local Backups:** Backup your complete website locally on your server with just one click.
* **FTP:** Easily backup and restore your backup using FTP.
* **Backup to Google Drive**
* **One-Click Restore:** Restore your website files and databases with a one-click restore.
* **Migration:** Stress-free migration to any domain or host.
* **Database Backups:** Backup your website's database only.

== Premium Features ==
* **Automatic Backups:** Choose to back up your website at regular intervals like Daily, Weekly, or Monthly. You can also customize the interval.
* **One-click Restore:** With Backuply, restoring your website is simple. Just click on the restore button next to the backup you want to restore from. Your entire backup will be downloaded and the changes will be applied to the website.
* **Selective Backup:** You have the option to choose from whether only files or database backups or full backups should be performed.
* **Website Migration:** You can easily migrate your website by restoring from one of the Cloud Backup options on the new website.
* **Website Cloning:** If you would like to clone your website for any purpose, Backuply can do that for you. Backuply will restore the data but replace the URLs and information as per the existing website. In this way, you can create multiple clones.
* **Backup to FTPS:** You can back up your site to an FTPS i.e. FTP over SSL / TLS.
* **Backup to SFTP:** Supports the SFTP protocol.
* **Backup to Dropbox**
* **Backup to Microsoft One Drive**
* **Backup to Amazon S3**
* **Backup to WebDAV**
* **Backup to S3 Compatible Storages:** Added support for DigitalOcean Spaces, Linode Object Storage, Vultr Object Storage, and Cloudflare R2.
* **Support for WP-CLI:** You can use Backuply through WP-CLI
* **Professional Support:** Get professional support and more features to make backup your website with [Backuply](https://backuply.com/pricing)


== Backups ==
Backup is a way of copying your data or files to a secure place, which can be used to restore your website in case of data loss. Backups are vital in securing the data that you have published or written. Backups with Backuply are easy and secure with support for multiple options of storage like local storage using FTP or using third-party services like Google Drive, Dropbox, Microsoft OneDrive, AWS S3 and WebDAV.
To make it even easier we support Automatic Backups with a customizable backup schedule.


== Restores == 
Restoring is just a One-Click process using Backuply. If the selected backup is available then Backuply will restore your backups safely. Restoring a backup will roll back your site in the exact same state as it was when the backup was created.


== Migration ==
Backuply creates a tar file of your whole WordPress install with the Database, so you can migrate your site to any host or location where WordPress can be installed. All you need to do is create a Backup of your WordPress install on a remote location, and that's it, It can be synced on any WordPress install with ease so you just need to restore the synced backup on the new location for Migration to happen.


== Frequently Asked Questions ==

Do you have questions related to Backuply? Use the following links :

1. [Docs](https://backuply.com/docs)
3. [Help Desk](https://backuply.deskuss.com)
2. [Support Forum](http://wordpress.org/support/plugin/backuply)

== How to install Backuply ==
Go To your WordPress install -> Plugins -> Add New Button -> In Search Box search For Backuply -> Click on Install.

== Screenshots ==

1. **Dashboard** manual backup and info.
2. **Settings** set backup settings like backup location, backup options and email to notify.
3. **Backup Locations** add remote locations to backup and restore from.
4. **Backup History** manage all your backups.
5. **Restore Process** easy to understand restore progress.
6. **Add Backup Location** with a fairly simple form to add a backup location.
7. **Backup Process** easy to understand backup progress.

== Changelog ==

= 1.2.2 (December 15 2023) =
* [Security-fix] There was a privilege check failure which has been fixed.

= 1.2.1 (December 07 2023) =
* [Structural Change] We have made some structural changes, now Backuply Free will be required for the Pro version to work.
* [Improvement] Now you can check quota of backup locations like OneDrive, Dropbox, and Google Drive.
* [Bug-Fix] There was issue with updation of Backuply Cloud quota which has been fixed.
* [Bug-Fix] There was issue while restoring using SFTP and FTPS which has been fixed.
* [Bug-Fix] There were some warnings on the settings page which has been fixed.
* [Bug-Fix] There was an issue related to deletion of Backuply Cloud backups when storage limt reached, which has been fixed.

= 1.2.0 (November 06 2023) =
* [Improvement] While restoring the permission of the root folder wont be updated.
* [Improvement] Logs have been improved to, while restoring all fatal error will be logged in Backuply logs.
* [Bug-Fix] There was a conflict with a few SMTP plugins, which has been fixed.
* [Bug-Fix] There was issue unsetting Email notification field which has been fixed.
* [Bug-Fix] On some servers the restore was failing as it was not able to create files, that has been fixed.
* [Task] Tested with WordPress 6.4.

= 1.1.9 (October 05 2023) =
* [Tweak] Now the timezone of the Backup will be the timezone of the WordPress.
* [Tweak] Memory limit fatal error will be logged in Backuply backup progress.
* [Bug-Fix] There was a function incompatibility issue when creating Backup to FTP.
* [Bug-Fix] There was a issue in FTPS when uploading the file, which has been fixed.
* [Bug-Fix] There was an issue with migration of website when migrating from  / to /example, which has been fixed.

= 1.1.8 (August 24 2023) =
* [Bug-Fix] There was issue while restoring, some users were getting error of unable to unzip, that has been fixed.
* [Tweak] Nag timings where updated.
* [Tweak] Database backups created after version 1.1.7 will delete old tables progressively while restoring, instead of deleting them all at once. This is to reduce the chance of breaking the site if something goes wrong during the restore process..

= 1.1.7 (July, 25 2023) =
* [Feature] Option to download Backuply Cloud backups.
* [Bug-Fix] There was an issue for some users while restoring, with a fatal error of duplicate entry in database restore, which has been fixed.
* [Tweak] The chunk size of Google Drive while restore has been increased to improve the speed of download.

= 1.1.6 (July, 05 2023) =
* [Bug-Fix] There was an issue while restoring of backup not able to unzip that has been fixed.
* [Bug-Fix] There was an issue while downloading backup through Backuply while restoring, that has been fixed.

= 1.1.5 (June, 29 2023) =
* [Bug-Fix] There was issue with Auto Backups for Trial users that has been fixed.
* [Bug-Fix] There was an issue while connecting to Backuply through the trial promo.

= 1.1.4 (June, 24 2023) =
* [Feature] Backuply Cloud now provides 10GB of default space which was set as 1 GB previously.
* [Bug-Fix] The quota of Backuply Cloud was not showing properly.
* [Bug-Fix] On restore a info file was not getting delete after completion of restore that has been fixed.

= 1.1.3 (June, 13 2023) =
* [Feature] Now use Backuply Cloud as your Backup location to keep your website safe.
* [Bug-Fix] There was an issue with Gdrive, backup was getting stuck.
* [Bug-Fix] A user faced a fatal error while listing backups that has been fixed.

= 1.1.2 (April, 3 2023) =
* [Bug-Fix] For some user backup was completing but not creating backup of all directories when backup of Database and Directories was selected that has been fixed.
* [Bug-Fix] Backup was failing at 100% when creating backup to Google Drive that has been fixed.
* [Bug-Fix] There was an issue while deleting local backups thats has been fixed.

= 1.1.1 (March, 31 2023) =
* [Bug-Fix] There was an issue with Backup and restore on sites with broken SSL that has been fixed.
* [Task] Tested with WordPress 6.2.

= 1.1.0 (February, 13 2023) =
* [Feature] Added Support for Wasabi S3 Compatible Object Storage.
* [Feature] Local backups created in Backuply version 1.1.0 and above can be synced.
* [Tweak] To initiate backup we were dependent on WP CRON, which was causing issue for some of our users, as WP Cron was getting stuck. So now manual backup dosen't uses WP CRON, WP CRON in backuply is only used by Automatic Backups.

= 1.0.9 (December, 24 2022) =
* [Security-Improvement] Removed all app keys.
* [Security-Improvement] Made restore logs even more secure with more randomness

= 1.0.8 (December, 22 2022) =
* [Security-Fix] Added index.html file and web.config to improve the protection of the backup folder.
* [Security-Fix] Made the backup folder have random strings to make the name be unpredictible.
* [Bug-Fix] There was an issue while creating database backup for some users that has been fixed.
* [Bug-Fix] There was issue loading last logs for some uses, that has been fixed.
* [Bug-Fix] For some user while downloading the Local Backup the progress bar was not updating that has been fixed.
* [Removed] Installing Backuply Pro from the Free version as per the WordPress guidelines.

= 1.0.7 (December, 19 2022) =
* [Security-Fix] The Google Drive App Secret key of Backuply was committed in the code. This is fixed. We have issued a new secret as well !
 
= 1.0.6 (December, 12 2022) = 
* [Bug-Fix] There was an issue while saving the additional file in settings page that has been fixed.
* [Bug-Fix] Issues related to PHP 8.1 and 8.0 compatibility have been fixed.
* [Bug-Fix] There was an issue while creating backuply config for some users that has been fixed.
* [Bug-Fix] When selecting check all files in Additional file option, all databases were getting selected to in exclude database, that has been fixed.
* [Bug-Fix] When selecting auto backup time the auto backup options weren't visible for some users that has been fixed.
* [Bug-Fix] For some users the backup was getting stuck at 17% sometimes that has been fixed.

= 1.0.5 (November 15, 2022) = 
* [Feature] Added support for WP-CLI, you can perform backup, restore, and sync right from your terminal.
* [Task] We have improved the promotion nags, so it doesn't cause inconvenience to our users.
* [Bug-Fix] There was a PHP warning being shown while syncing or downloading from Dropbox that has been fixed.
* [Bug-Fix] There was an issue while deleting GDrive backups that has been fixed.

= 1.0.4 (October 14, 2022) =
* [Feature] Added support for S3 Compatible backup locations like DigitalOcean Spaces, Linode Object Storage, Vultr Object Storage, and Cloudflare R2.
* [Feature] Added support for Server Side Encryption for AWS.
* [Task] Google Drive is now available for all users.
* [Bug-Fix] Part Number while downloading for restore in AWS had an issue. That has been fixed.
* [Bug-Fix] For some user restore was getting stuck at repairing database status, that has been fixed.

= 1.0.3 (September 20, 2022) =
* [Improvement] Added Backup Download progress
* [Task] The Backuply nag will now appear after 7 days instead of 1 day.
* [Bug-Fix] The last backup time was shown from 1970 when no backup was created. This has been fixed.
* [Bug-Fix] Backup on Google was failing for some users. This has been fixed.
* [Bug-Fix] On failure, in some cases the partial backup file was not cleaned. This has been fixed.
* [Bug-Fix] At times, the backup nag was not getting dismissed. This has been fixed.

= 1.0.2 (August 18, 2022) =
* Exclude Files, Directories or Database tables from a backup
* Logs for every backup
* Minor bug fixes

= 1.0.1 (July 22, 2022) =
* Added Last Logs of Backups and Restore

= 1.0.0 (July 21, 2022) =
* Released Plugin


