# ilias-oc
An ILIAS Repository Object plugin for Opencast 6

This plugin creates a tight intgration of Opencast 6(for older versions see older [releases](https://github.com/TIK-NFL/ilias-oc-plugin/releases)) and ILIAS.
This plugin also requires to a workflow handler for Opencast, which enables distributing the files in a way that
are usable by this plugin. This plugin is currently only available in the [Github Repo](https://github.com/pascalseeland/opencast)
and not part of the official Opencast Distribution.

## Installation

__REQUIRED__ Databases: MySQL/MariaDB

It __MUST__ be installed into `Customizing/global/plugins/Services/Repository/RepositoryObject/`.

The plugin folder __MUST__ be named 'Matterhorn'.

## Configuration

### Plugin-Configuration

#### Opencast directory

This __MUST__ be **org.opencastproject.storage.dir**.

For nginx add the config:
```
location /__ilias_xmh_mh_directory__/ {
   internal;
   alias ${org.opencastproject.storage.dir}/;
}
```

For apache add the config:
```
XSendFilePath ${org.opencastproject.storage.dir}
```

#### XSendfile header

For nginx select `X-Accel-Redirect`.
For apache select `X-Sendfile` and enable **mod_xsendfile**.

#### Distribution directory

This __MUST__ be the path were episodes are available after upload, so this plugin can serve them to users.

For nginx add the config:
```
location /__ilias_xmh_distribution_directory__/ {
   internal;
   alias ${distribution_directory}/;
}
```

For apache add the config:
```
XSendFilePath ${distribution_directory}
```

#### Upload Workflow

This workflow is used for uploads from ilias to opencast.
The workflow __MUST__ support the configuration:
- `flagForCutting` ["true", "false"]
- `straightToPublishing` ["true", "false"]

For cutting the workflow must create a single track preview with flavor "presentation/preview", "presenter/preview" or "composite/preview".
This preview __MUST__ be published on the api channel.

#### Publisher
The publisher used to create new Opencast series, e.g. "University of Stuttgart, Germany".
This value is optional.
