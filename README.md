SFN-Prune-Old-MU-Users
======================

This prunes old users from an MU site that have not acvitated their accounts.

By default it will 'batch' users to 200 at at time and only look for users that are 2 weeks old. The main cron job runs hourly so that you can catch up in short order if you have a bunch of accounts that are not activated.

### Filters

`sfn_prune_time_ago` Allows you to change the 2 weeks ago time to whatever you want. Expects a UNIX time stamp.

So if you wanted to only prune accounts that are a month old...

```php
function change_time_ago( $time ){
	$time_ago = strototime( '1 month ago', time() );
	return $time_ago;
}
add_filter( 'sfn_prune_time_ago', 'change_time_ago' );
```

`sfn_prune_how_many_users` Allows you to change the number of users in a batch. Defaults to 200

If we wanted to prune 300 accounts at a time

```php
function prune_more_users( $number ){
	return 300;
}
add_filter( 'sfn_prune_how_many_users', 'prune_more_users' );
```

### Changelog

#### 1.0

- plugin starts, we all have to start somewhere
