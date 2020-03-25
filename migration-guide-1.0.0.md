# Migration guide to 1.0.0

This Guide is for upgrading from any version below `1.0.0`.
Version `1.0.0` merged all database updates and renamed the installation directory.

## Before updating the plugin
1. First read the [Readme](README.md).
2. Configure Opencast as described in the readme:
    - external API
    - URL Signing
    - API User
3. Add the role `ROLE_USER_{OPENCASTUSER}` to all existing series and episodes created by the plugin (and remove the old role, which is the Ilias user login name)
4. All existing episodes must be republished to the api channel, this can take some time.
   So you can use a workflow to publish to the api channel and the old Ilias publication channel at the same time to serve the old plugin version.
   Api channel publications:
    - finished episodes after trimming must be published without preview tracks
    - episodes not already trimmed must be published with a preview track

## Update the plugin
1. Update the plugin to `0.5.4` and make all database updates.
2. Checkout the version `1.0.0` of the plugin
3. Rename the plugin directory to `Opencast`
4. Update the Ilias database plugin entry:
   ```sql
   DELETE FROM il_plugin WHERE db_version = 0 AND name='Opencast';
   UPDATE il_plugin SET db_version=1,name='Opencast' WHERE plugin_id = 'xmh';
   ```
6. Update the plugin version in the Ilias admin ui
