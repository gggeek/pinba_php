v0.3 - 6/12/2022

* fixed default value for `$flag` argument of `pinba_timers_get`
* fixed return value of `pinba_tag_delete`
* fixed: when creating two or more timers with the same tag values, but tags in different order, they would not be merged
* added: class `PinbaClient` - with a few limitations, see README
* added method: `pinba_reset`
* improved: added more sanity checks of function arguments values, closely matching the behaviour of the extension
* improved test code coverage

v0.2 - 5/12/2022

* added constants: `PINBA_FLUSH_ONLY_STOPPED_TIMERS`, `PINBA_FLUSH_RESET_DATA`, `PINBA_ONLY_RUNNING_TIMERS`, `PINBA_AUTO_FLUSH`,
  `PINBA_ONLY_STOPPED_TIMERS`
* added methods: `pinba_timer_add`, `pinba_timers_get`, `pinba_schema_set`, `pinba_server_name_set`, `pinba_request_time_set`
  `pinba_tag_set`, `pinba_tag_get`, `pinba_tag_delete`, `pinba_tags_get`
* fixed return value for methods: `pinba_script_name_set`, `pinba_hostname_set`
* fixed: `pinba_flush` now stops all timers by default. It also supports 2nd argument `$flags` to change its behaviour
* added non-API methods: `Pinba::ini_set` and `Pinba::ini_get`
* made sure CI tests can successfully connect to the pinba servers and query them

v0.1 - 3/12/2022

Changes compared to the previous state (2013 commits): this thing now works well enough to send data to a Pinba server

* fix bugs with non-existing class method being called
* fix float fields in protobuf messages sent
* various API compatibility fixes
* add CI tests using GitHub Actions
