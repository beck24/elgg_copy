# Elgg Copy

A tool for cloning an elgg installation from a master installation (production?) to a development
installation.  The development installation will request all components of the installation from
the master installation.  They will be downloaded, configured, and replace the existing data on dev.
This is useful when you need to get the latest code from a production site while trying to debug something.

Note that this uses some system commands such as mysqldump, gzip, curl, etc and will likely not work
for windows.  Who's using windows for dev anyway?

# Installation

Clone/unzip the elgg_copy plugin to mod/elgg_copy on both the development install and the master install

On the settings page a "Request Key" will be generated.  Copy the request key from the master installation
to the plugin settings of dev.
Enter the URL of the master installation in the plugin settings of dev.

Check the boxes corresponding to what should be copied from master - mod/data/database

No settings are required on the master installation.

# Usage

A button will be added to the admin control widget.  Clicking that button will initiate the sync
from master.

# Security

Master will only provide the data to download on a hard-to guess URL based on a cryptographic request key.
This key can be regenerated in the settings of master if there is reason to think it has been
compromised.  It is recommended, however, that this plugin should be disabled on master the majority of the time
and only activated when a sync needs to happen.

The ability to sync the mod directory requires the mod directory of the dev environment to be
writable by the server.  This isn't recommended for production but should be fine for a local dev
environment.

One additional note - the entire contents of the site will be transferred over standard
http protocol, it is recommended that this only be used on production sites that are secured
with ssl.

This is a tool of convenience, use at your own risk.

# Notes

The database prefix must be the same on both installations
