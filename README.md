# ilias-oc
An ILIAS plugin for OpenCast 3.x

This plugin creates a tight ingration of Opencast 3.x(1.6 and 2.0 see older releases) and ILIAS.
This plugin also requires to a workflow handler for Opencast, which enables distributing the files in a way that
are usable by this plugin. This plugin is currently only available in the [Bitbucket Repo](https://bitbucket.org/pascalgrube/matterhorn/branch/distribution-ilias)
and not part of the official Opencast Distribution.

## Configuration

The plugin folder MUST be named 'Matterhorn'.

### Plugin-Configuration

#### XSendfile header

For ngnix set this to `X-Accel-Redirect` and add the config:
```
location /__ilias_xmh_X-Accel__/ {
   internal;
   root /${org.opencastproject.storage.dir}/;
}
```

For apache set this to `X-Sendfile` and add the config:
```
XSendFilePath /${org.opencastproject.storage.dir}/
```

#### Upload directory

This MUST be the path were episodes are available after upload, so this plugin can serve them to users.

Databases: MySQL/MariaDB only
