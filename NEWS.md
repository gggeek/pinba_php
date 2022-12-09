vXXX - unreleased

* improved: replicate extension behaviour: convert tag values to string upon setting them
* improved: replicate extension behaviour: delete stopped timers when calling `flush` even if `pinba.enabled=0`

v0.4 - 8/12/2022

* fixed a bug in merging timers tags, introduced in v0.3
* fixed: request tags were not being properly sent to the server
* fixed: parsing IPv6 addresses, or addresses in the form `[127.0.0.1]:8080` in `pinba_server` configuration option
* fixed: return type of `pinba_timers_get`
* improved: added support for `pinba.auto_flush` configuration option
* improved: report automatically to Pinba the script's http status code by default
* improved: support custom `hit_count` values on timer creation
* improved: added 4th parameter `$hit_count = 1` to `pinba_timer_add`
* improved: support (undocumented) function `pinba_get_data`
* improved: support (undocumented) function `pinba_reset`
* improved: replicate extension behaviour: return `false` instead of `null` on a failed `pinba_tag_get` call
* improved: replicate extension behaviour: default `req_count` is 1 in data from `get_info()`, but 0 as sent to the server
* improved: replicate extension behaviour: once flushed, timers are not visible any more in `pinba_get_info` and `pinba_timers_get` calls
* improved: replicate extension behaviour: a `PinbaClient` object will not flush automatically upon destruction if
  it was flushed manually beforehand
* improved: added one more sample file: doc/measure_body_size.php

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
