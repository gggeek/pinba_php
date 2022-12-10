Polyfill-Pinba
==============

Pure-php reimplementation of the "PHP extension for Pinba".

See http://pinba.org for the original.

## Requirements

PHP 5.3 or any later version.

A Pinba server to send the data to. Known servers include http://pinba.org/ and https://github.com/badoo/pinba2. Both
are available as Docker Container images for trying out.

## Installation

    composer require gggeek/polyfill-pinba

then set up configuration settings `pinba.enabled` and `pinba.server` in `php.ini` as described at
https://github.com/tony2001/pinba_engine/wiki/PHP-extension#INI_Directives

That's all.

**NB** gathering of metrics and sending them to the server is disabled by default. You _have_ to enable it via ini settings,
unless you have added explicit calls to the pinba api in your php code.

## Usage

See the API described at https://github.com/tony2001/pinba_engine/wiki/PHP-extension

A trivial usage example can be found in [doc/sample.php](doc/sample.php).

For viewing the gathered metrics, check out https://github.com/intaro/pinboard, https://github.com/pinba-server/pinba-server
or https://github.com/ClickHouse-Ninja/Proton

## Compatibility

We strive to implement the same API as Pinba extension ver. 1.1.2.

As for the server side, the library is tested for compatibility against both a Pinba server and a Pinba2 one.

Features not (yet) supported:
- Timers data misses `ru_utime` and `ru_stime` members. This is true also for timers added to `PinbaClient` instances

Known issues - which cannot / won't be fixed:
- lack in precision in time reporting: the time reported for page execution will be much shorter with any php code than
  it can be measured with a php extension. We suggest thus not to take the time reported by this package as an absolute
  value, but rather use it to check macro-issues, such as a page taking 10 seconds to run, or 10 times as much as another
  page. In the demo file [doc/sample.php](doc/sample.php) we showcase how to make time measurement as precise as possible
- impact on system performances: the cpu time and ram used by this implementation (which runs on every page of your site!)
  are also bigger than the resources used by the php extension. It is up to you to decide if the extra load added to
  your server by using this package is worth it or not, esp. for heavily loaded production servers
- the warnings raised when incorrect data is passed to the pinba php functions are of severity `E_USER_WARNING` instead of
  `E_WARNING`
- in the data reported by `pinba_get_info` and reported to the Pinba server, `doc_size` has always a value of 0. This
  can be worked around by using an instance of `PinbaClient` and calling `setDocumentSize` - see
  [doc/measure_body_size.php](doc/measure_body_size.php) for an example
- in the data reported to the Pinba server, `memory_footprint` has always a fixed value of 0 or is not reported at all.
  Again, using a `PinbaClient` instance can fix that - but there is no php function available that I know of which can
  report the equivalent usage of the `mallinfo` C call done by the php extension
- ini setting `pinba.resolve_interval` is not supported and most likely never will
- the default value reported to the Pina engine for the `schema` field is an empty string, rather than not being set at
  all. This results in the database table storing a value of '<empty>' instead of NULL. At the same time, sending a
  value of NULL makes the server-side engine re-use the last non-null value from a previous pinba packet, which seems a
  faulty behaviour
- the `pinba_reset` call does delete all exiting timers, unlike what the same function from the php extension does.
  Again, the upstream behaviour does feel faulty

## Notes

Includes code from the Protobuf for PHP lib by Iván -DrSlump- Montes: https://github.com/drslump/Protobuf-PHP

Other known packages exist implementing the same idea, such as: https://github.com/vearutop/pinba-pure-php

## FAQ

- **Q:** Can I run the polyfill in conjunction with the pinba php extension? **A:** yes, although I fail to see the
  reason why you would do that. When doing so, unless taking care to selectively disable either the php extension
  or this bundle (f.e. via calls to `Pinba::ini_set` in your code), you will get double data reported to the Pinba server

## Running tests

The recommended way to run the library's test suite is via the provided Docker containers and the corresponding
Docker Compose configuration.
A handy shell script is available that simplifies usage of Docker and Docker Compose.

The full sequence of operations is:

    ./tests/ci/vm.sh build
    ./tests/ci/vm.sh start
    ./tests/ci/vm.sh runtests
    ./tests/ci/vm.sh stop

    # and, once you have finished all testing related work:
    ./tests/ci/vm.sh cleanup

By default, tests are run using php 7.4 in a Container based on Ubuntu 20 Focal. The data is sent to a container running
the original Pinba server - it can also be configured to be sent to a container running the Pinba2 server instead.

You can change the version of PHP and Ubuntu in use by setting the environment variables `PHP_VERSION` and `UBUNTU_VERSION`
before building the containers.

You can switch the target Container used for the testsuite to the one running Pinba2 by setting the environment variables
`PINBA_DB_SERVER=pinba2`, `PINBA_SERVER=pinba2` and `PINBA_PORT=3002` before starting the containers.

### Testing tips

* to debug the communication between php and the Pinba server, it is possible to use tcpdump within the `php` container:

        sudo tcpdump udp -w packets.cap

  once the capture file is saved, it can be analyzed by fe. opening it in Wireshark. To improve the ability of
  Wireshark of decoding the protobuf-formatted messages, set up its configuration as described at https://wiki.wireshark.org/Protobuf.md.
  The `.proto` file describing the messages used by Pinba can be found at https://github.com/badoo/pinba2/blob/master/proto/pinba.proto

## License

Use of this software is subject to the terms in the [license](LICENSE) file

[![License](https://poser.pugx.org/gggeek/polyfill-pinba/license)](https://packagist.org/packages/gggeek/polyfill-pinba)
[![Latest Stable Version](https://poser.pugx.org/gggeek/polyfill-pinba/v/stable)](https://packagist.org/packages/gggeek/polyfill-pinba)
[![Total Downloads](https://poser.pugx.org/gggeek/polyfill-pinba/downloads)](https://packagist.org/packages/gggeek/polyfill-pinba)

[![Build Status](https://github.com/gggeek/pinba_php/actions/workflows/ci.yml/badge.svg)](https://github.com/gggeek/pinba_php/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/gggeek/pinba_php/branch/master/graph/badge.svg)](https://app.codecov.io/gh/gggeek/pinba_php)
