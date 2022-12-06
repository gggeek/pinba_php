Polyfill-Pinba
==============

Pure-php reimplementation of the "PHP extension for Pinba".

See http://pinba.org for the original.

*WORK IN PROGRESS*

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

For viewing the gathered metrics, check out https://github.com/intaro/pinboard, https://github.com/pinba-server/pinba-server
or https://github.com/ClickHouse-Ninja/Proton

## Compatibility

We strive to implement the same API as Pinba extension ver. 1.1.2.

As for the server side, the library is tested for compatibility against both a Pinba server and a Pinba2 one.

Features not (yet) supported:
- ini settings `pinba.auto_flush` and `pinba.resolve_interval`
- 3rd argument `$hit_count` in function `pinba_timer_start` is accepted but not used
- in the data reported by `pinba_get_info` and reported to the Pinba server, `doc_size` has always a value of 0. This
  can be worked around by using an instance of `PinbaClient` and calling `setDocumentSize`
- in the data reported to the Pinba server, the following information has always a fixed value or is not reported at all:
  `status`, `memory_footprint`, `requests`. Again, using a `PinbaClient` instance can fix that
- Timers data misses `ru_utime` and `ru_stime` members. This is true also for timers added to `PinbaClient` instances

Known issues - which cannot be fixed:
- lack in precision in time reporting: the time reported for page execution will be much shorter with any php code than
  it can be measured with a php extension. We suggest thus not to take the time reported by this package as an absolute
  value, but rather use it to check macro-issues, such as a page taking 10 seconds to run, or 10 times as much as another
  page
- impact on system performances: the cpu time and ram used by this implementation (which runs on every page of your site!)
  are also bigger than the resources used by the php extension. It is up to you to decide if the extra load added to
  your server by using this package is worth it or not, esp. for heavily loaded production servers
- the warnings raised when incorrect data is passed to the pinba php functions are of severity E_USER_WARNING instead of
  E_WARNING

## Notes

Includes code from the Protobuf for PHP lib by Iván -DrSlump- Montes: https://github.com/drslump/Protobuf-PHP

Other known packages exist implementing the same idea, such as: https://github.com/vearutop/pinba-pure-php

## Running tests

The recommended way to run the library test suite is via the provided Docker containers.
A handy shell script is available that simplifies usage of Docker.

The full sequence of operations is:

    ./tests/ci/vm.sh build
    ./tests/ci/vm.sh start
    ./tests/ci/vm.sh runtests
    ./tests/ci/vm.sh stop

    # and, once you have finished all testing related work:
    ./tests/ci/vm.sh cleanup

By default, tests are run using php 7.4 in a Container based on Ubuntu 20 Focal.
You can change the version of PHP and Ubuntu in use by setting the environment variables PHP_VERSION and UBUNTU_VERSION
before building the Container.

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
