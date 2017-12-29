# ilias-oc
An ILIAS plugin for OpenCast 3.x

This plugin creates a tight ingration of Opencast 3.x(1.6 and 2.0 see older releases) and ILIAS.
This plugin also requires to a workflow handler for Opencast, which enables distributing the files in a way that
are usable by this plugin. This plugin is currently only available in the [Bitbucket Repo](https://bitbucket.org/pascalgrube/matterhorn/branch/distribution-ilias)
and not part of the official Opencast Distribution.

## Configuration

The plugin folder MUST be named 'Matterhorn'.

ngnix:
```
location /__ilias_xmh_X-Accel__/ {
   internal;
   root /${org.opencastproject.storage.dir}/;
}
```

apache:
```
XSendFilePath /${org.opencastproject.storage.dir}/
```

Databases: MySQL/MariaDB only
