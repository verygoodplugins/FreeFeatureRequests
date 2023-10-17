# FreeFeatureRequests

This is a [FreeScout](https://freescout.net/) module which integrates FreeScout with [Simple Feature Requests for WordPress](https://simplefeaturerequests.com/)

Features:

- Link FreeScout conversations with feature requests in WordPress
- Create new feature requests from within FreeScout
     - Set the status (supports custom statuses)
     - Set the category
     - Title is automatically populated from the conversation title
     - Subscribe request to any existing WordPress user
- View linked feature requests in the FreeScout sidebar

## Wish list

- [ ] Webhook notifications when a request status is updated (similar to the Jira module)
- [ ] View request status in the FreeScout sidebar

--------------------

## Changelog

### 1.0.0 on October 17, 2023

- Initial release

--------------------

## Installation

These instructions assume you installed FreeScout using the [recommended process](https://github.com/freescout-helpdesk/freescout/wiki/Installation-Guide), the "one-click install" or the "interactive installation bash-script", and you are viewing this page using a macOS or Ubuntu system.

Other installations are possible, but not supported here.

1. Download the [latest release of FreeFeatureRequests](https://github.com/verygoodplugins/FreeFeatureRequests/releases).

2. Unzip the file locally.

3. Copy the folder into your server using SFTP.

   ```sh
   scp -r ~/Desktop/freehelp-root@freescout.example.com:/var/www/html/Modules/FreeFeatureRequests/
   ```

4. SSH into the server and update permissions on that folder.

   ```sh
   chown -R www-data:www-data /var/www/html/Modules/FreeFeatureRequests/
   ```

5. Access your admin modules page like https://freescout.example.com/modules/list.

6. Find **FreeFeatureRequests** and click ACTIVATE.

7. Copy the included WordPress helper plugin from `/WordPress-Plugin/simple-feature-requests-api` to your `/wp-content/plugins/` directory on the WordPress site.

8. Activate the WordPress plugin.

9. In the WordPress admin, go to your user profile and create a new application password.

10. In FreeScout, go to Settings >> Feature Requests and add the URL to your Wordpress site, your admin username, and application password generated in step #9.

11. Save the settings and the connection should show as Active.