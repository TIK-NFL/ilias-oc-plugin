# Migration guide to 1.0.0

This Guide is for upgrading from any version below `1.0.0`.
Version `1.0.0` merged all database updates and renamed the installation directory.

## Before updating the plugin
1. First read the [Readme](README.md).
2. Configure Opencast as described in the readme:
    - external API
    - URL Signing
    - API User (The API user should also have the role `ROLE_ADMIN`, because the old plugin version requires it)
3. Add the role `ROLE_USER_{OPENCASTUSER}` to all existing series created by the plugin (and remove the old role, which is the Ilias user login name)
   Use the "Update Event permissions" button to apply the change to the episodes as well.
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
5. Configure the plugin in the Ilias admin ui
    - enter Opencast url and save
    - select workflows and save
6. Update the plugin in the Ilias admin ui

## Clean up
1. You can now remove the `ROLE_ADMIN` from the opencast api user.
2. Sendfile is not used in version `1.0.0` so you can disable it.
3. The network file share of opencast and ilias can now be disabled and removed.
4. Remove old config entries like digest user from the ilias database `rep_robj_xmh_config` table.
