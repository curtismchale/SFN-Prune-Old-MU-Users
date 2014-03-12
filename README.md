SFN-Prune-Old-MU-Users
======================

This prunes old users from an MU site that have not acvitated their accounts.

By default it will 'batch' users to 200 at at time and only look for users that are 2 weeks old. The main cron job runs daily so as long as you don't get more than 200 dead accounts a day this will catch up in a few days.

### Filters

`sfn_prune_time_ago` Allows you to change the 2 weeks ago time to whatever you want. Expects a UNIX time stamp.

`sfn_prune_how_many_users` Allows you to change the numder of users in a batch. Defaults to 200

### Changelog

#### 1.0

- plugin starts, we all have to start somewhere
