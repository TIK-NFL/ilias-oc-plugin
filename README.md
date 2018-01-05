# ilias-oc
An ILIAS plugin for OpenCast 3.x

This plugin creates a tight ingration of Opencast 3.x(1.6 and 2.0 see older releases) and ILIAS.
This plugin also requires to a workflow handler for Opencast, which enables distributing the files in a way that
are usable by this plugin. This plugin is currently only available in the [Bitbucket Repo](https://bitbucket.org/pascalgrube/matterhorn/branch/distribution-ilias)
and not part of the official Opencast Distribution.

## Installation

__REQUIRED__ Databases: MySQL/MariaDB

The plugin folder __MUST__ be named 'Matterhorn'.

## Configuration

### Plugin-Configuration

#### XSendfile header

For ngnix set this to `X-Accel-Redirect` and add the config:
```
location /__ilias_xmh_mh_directory__/ {
   internal;
   root ${org.opencastproject.storage.dir}/;
}
```

For apache set this to `X-Sendfile` and add the config:
```
XSendFilePath ${org.opencastproject.storage.dir}/
```

#### Upload directory

This __MUST__ be the path were episodes are available after upload, so this plugin can serve them to users.

For ngnix add the config:
```
location /__ilias_xmh_upload_directory__/ {
   internal;
   root ${upload_directory}/;
}
```

For apache add the config:
```
XSendFilePath ${upload_directory}/
```
