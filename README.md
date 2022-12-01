Polyfill-Pinba
==============

Pure-php reimplementation of the "PHP extension for Pinba"

See http://pinba.org for the original

Includes code from the Protobuf for PHP lib by Iván -DrSlump- Montes:
https://github.com/drslump/Protobuf-PHP

Other known packages implementing the same idea: https://github.com/vearutop/pinba-pure-php

## Requirements

PHP 5.3 or any later version.

A Pinba server to send the data to. Known servers include http://pinba.org/ and https://github.com/badoo/pinba2. Both
are available as Docker Container images for trying out.

## Installation

    composer require gggeek/polyfill-pinba

## Usage

See the API described at https://github.com/tony2001/pinba_engine/wiki/PHP-extension

For viewing the gathered metrics, check out https://github.com/intaro/pinboard, https://github.com/ClickHouse-Ninja/Proton

## License

Use of this software is subject to the terms in the [license](LICENSE) file

[![License](https://poser.pugx.org/gggeek/polyfill-pinba/license)](https://packagist.org/packages/gggeek/polyfill-pinba)
[![Latest Stable Version](https://poser.pugx.org/gggeek/polyfill-pinba/v/stable)](https://packagist.org/packages/gggeek/polyfill-pinba)
[![Total Downloads](https://poser.pugx.org/gggeek/polyfill-pinba/downloads)](https://packagist.org/packages/gggeek/polyfill-pinba)

[![Build Status](https://github.com/gggeek/pinba_php/actions/workflows/ci.yml/badge.svg)](https://github.com/gggeek/pinba_php/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/gggeek/pinba_php/branch/master/graph/badge.svg)](https://app.codecov.io/gh/gggeek/pinba_php)
