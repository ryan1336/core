[![StyleCI](https://styleci.io/repos/75443611/shield?branch=development&style=flat)](https://styleci.io/repos/75443611)
[![Code Climate](https://codeclimate.com/github/VATSIM-UK/core/badges/gpa.svg)](https://codeclimate.com/github/VATSIM-UK/core)
[![Build Status](https://travis-ci.org/VATSIM-UK/core.svg?branch=production)](https://travis-ci.org/VATSIM-UK/core)

## Upgrade Notes

The following are the upgrade notes for deploying in production.

### All Versions

1. Stop the queue and TeamSpeak daemon
2. Disable cronjobs
3. Run `composer install --optimize-autoloader --no-dev` (dev: `composer install`)
4. Run `php artisan migrate --step --force --no-interaction`
6. Run `npm install`
7. Run `npm run prod` (dev: `npm run dev`)
8. **Perform version-specific upgrade steps (below)**
9. Enable all cronjobs
10. Restart the queue and TeamSpeak daemon

### 3.4.0

* Run `php artisan storage:link`
* Add `MAPS_API_KEY` in `.env`

### Older Versions

To upgrade from older versions, check the `README.md` file for that release.

---

## Changes to Issue Tracking

We are making changes to the way we are managing our issue tracking. We are moving away from GitHub Issues to Atlassian's JIRA.

For details on how to continue contributing to CORE using the new issue tracker, see the updated [Contribution Guide](CONTRIBUTING.md) and the new [Issue Tracking Guide](ISSUE_TRACKING.md) (which explains some of the finer points of the new system).

If you have any questions or problems with the new system, reach out to us via the #WebServices channel in the VATSIM UK Slack.
