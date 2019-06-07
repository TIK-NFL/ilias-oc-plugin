# ilias-oc
An ILIAS Repository Object plugin for Opencast 6

This plugin creates a tight intgration of Opencast 6 and ILIAS.
This plugin also requires a workflow handler for Opencast, which enables distributing the files in a way that are usable by this plugin.
This plugin is available in the [Github Repo](https://github.com/pascalseeland/opencast).

## Installation

__REQUIRED__ Databases: MySQL/MariaDB

It __MUST__ be installed into `Customizing/global/plugins/Services/Repository/RepositoryObject/`.

The plugin folder __MUST__ be named `Opencast`.

## Configuration

### Plugin

#### Upload Workflow

This workflow is used for uploads from ilias to opencast and __MUST__ have the `upload` tag to be recognized by this plugin.
The workflow __MUST__ support the configuration:
- `flagForCutting` ["true", "false"]
- `straightToPublishing` ["true", "false"]

For cutting the workflow must create a single track preview with flavor "presentation/preview", "presenter/preview" or "composite/preview".
This preview __MUST__ be published on the api channel.

#### Trim Workflow

This workflow is used for trim from the plugins trim editor.
It __MUST__ have the `editor` tag to be recognized by this plugin.
The workflow __MUST__ support the configuration:
- `start` integer the start duration in seconds
- `end` integer the end duration in seconds
- `tracks` array the ids of the tracks to trim and publish

#### Publisher
The publisher used to create new Opencast series, e.g. "University of Stuttgart, Germany".
This value is optional.

### Opencast

#### URL Signing
TODO