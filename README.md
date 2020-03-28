# ilias-oc [![CI](https://github.com/TIK-NFL/ilias-oc-plugin/workflows/CI/badge.svg)](https://github.com/TIK-NFL/ilias-oc-plugin/actions?query=workflow%3ACI)
An ILIAS Repository Object plugin for Opencast 5 and newer.

This plugin creates a tight integration of Opencast and ILIAS.
This plugin also requires a workflow handler for Opencast, which enables distributing the files in a way that are usable by this plugin.
This plugin is available in the [Github Repo](https://github.com/pascalseeland/opencast).

## Installation

__REQUIRED__ Databases: MySQL/MariaDB

It __MUST__ be installed into `Customizing/global/plugins/Services/Repository/RepositoryObject/`.

The plugin folder __MUST__ be named `Opencast`.

In production environments you can delete the `test/` directory.

## Configuration

### Upload workflow
Select a matching workflow as defined [below](#upload-workflow-definition).

### Trim workflow
Select a matching workflow as defined [below](#trim-workflow-definition).

### Publisher
The publisher used to create new Opencast series, e.g. "University of Stuttgart, Germany".
This value is optional.

## Opencast
An Opencast Service with enabled external API is required.
The servers external API must be accessible by Ilias and the Ilias users directly.

Currently the minimum required API version is `v1.1.0` (Opencast 5.x).

### API User
To access the [API of Opencast](https://docs.opencast.org/develop/admin/configuration/external-api/) from Ilias an authorized user is required.
The user need the following roles:
- `ROLE_API`
- `ROLE_API_EVENTS_CREATE`
- `ROLE_API_EVENTS_VIEW`
- `ROLE_API_EVENTS_DELETE`
- `ROLE_API_EVENTS_METADATA_EDIT`
- `ROLE_API_EVENTS_PUBLICATIONS_VIEW`
- `ROLE_API_SERIES_CREATE`
- `ROLE_API_SERIES_VIEW`
- `ROLE_API_SERIES_METADATA_EDIT`
- `ROLE_API_WORKFLOW_INSTANCE_CREATE`
- `ROLE_API_WORKFLOW_INSTANCE_VIEW`
- `ROLE_API_WORKFLOW_DEFINITION_VIEW`

Create this user in Opencast and enter it in the plugin configuration.
This plugin will create new series and episodes with a acl containing `ROLE_USER_{OPENCASTUSER}`, so they can be accessed by the plugin.

### URL Signing
In order to use URL signing the [Opencast Stream Security](https://docs.opencast.org/develop/admin/configuration/stream-security/) must be enabled.
Follow the Opencast documentation to do so.

### Upload workflow definition
This workflow is used for uploads from ilias to opencast and __MUST__ have the `upload` tag to be recognized by this plugin.
The workflow __MUST__ support the configuration:
- `flagForCutting` ["true", "false"]

If `flagForCutting` is set to "true" a [Preview Publication](#preview-publication) __MUST__ be published.
If `flagForCutting` is set to "false" a [Non Preview Publication](#non-preview-publication)  __MUST__ be published.

### Trim workflow definition

This workflow is used for trim from the plugins trim editor.
It __MUST__ have the `editor` tag to be recognized by this plugin.
The workflow __MUST__ support the configuration:
- `start` integer the start duration in seconds
- `end` integer the end duration in seconds

The workflow __MAY__ support additional configuration:
- `hide_presenter_video` as described in [opencast](https://docs.opencast.org/r/6.x/admin/workflowoperationhandlers/select-streams-woh/#workflow-properties)
- `hide_presentation_video` as described in [opencast](https://docs.opencast.org/r/6.x/admin/workflowoperationhandlers/select-streams-woh/#workflow-properties)

The workflows __MUST__ publish a [Non Preview Publication](#non-preview-publication) to the api channel.

### Schedule workflow definition

This workflow is used by the capture agents for scheduled episodes.
The workflow __MUST__ publish to the api channel.
It __MUST__ either publish a [Preview Publication](#preview-publication) or a [Non Preview Publication](#non-preview-publication).

### Non Preview Publication
This publication is for finished episodes, that can be published in the ilias plugin.
The publication __MAY__ contain a search preview for the episodes list view, the search preview __MUST__ have the flavor `*/search+preview`.
The media files of the publication __SHOULD__ have the flavor `*/delivery` and mediatype `video/mp4`.

### Preview Publication
This publication is for the trim editor.
It __SHOULD__ only contain a single track preview with flavor "presentation/preview", "presenter/preview" or "composite/preview".
This preview __MUST__ be published on the api channel with the tag `preview` on the track.


## FAQ
### What happens if a series created by this plugin is deleted in Opencast?
The Ilias Opencast object will not be deleted automatically, but it will not display any episodes and no exception will be thrown in the overview UIs of that particular Ilias Opencast object.
Only opening the properties tab of that Ilias Opencast object will throw an exception.
### What happens if a Ilias Opencast object?
The corresponding series and episodes are not deleted in Opencast.
### Which episodes are displayed on the overview page?
The episode must be published in Ilias, this means the episode id is in the `rep_robj_xmh_rel_ep` database table.
The episode must be part of the series corresponding to the Ilias Opencast object and must be have the status `EVENTS.EVENTS.STATUS.PROCESSED`.
The episode must have a publication on the api channel and the must not have a track with the preview tag.
