# Ratchet Revolt Prometheus Exporter

### What is Revolt?

Revolt is a rock-solid event loop for concurrent PHP applications.

-   https://revolt.run/
-   https://github.com/revoltphp/event-loop

### What is Ratchet?

Ratchet is a loosely coupled PHP library providing developers with tools to create real time, bi-directional applications between clients and servers over WebSockets.

-   http://socketo.me/
-   https://github.com/ratchetphp/Ratchet

### What is ReactPHP event loop?

Ratchet based on ReactPHP event loop but to use native fibers, the loop can be replaced to Revolt event loop by ReactAdaptor

-   https://github.com/amphp/react-adapter
-   https://github.com/Rekryt/react-adapter - for Amphp v3

### What is prometheus_client_php?

This library uses Redis or APCu to do the client side aggregation. If using Redis, we recommend running a local Redis instance next to your PHP workers.

-   https://github.com/PromPHP/prometheus_client_php

## Installation & Usage
```shell
cp .env.example .env
docker-compose up -d
```

HTTP server starts at: http://`${HTTP_HOST}`:80/metrics

## Usage (docker)

```shell
docker build -t ratchet_revolt .
docker run -it -p 80:80 ratchet_revolt
```

## Usage (php)

```
composer install
php index.php
```

### Example
```injectablephp
// Example gauge from mysql
$gauge = $registry->getOrRegisterGauge('rpe', 'testTable', 'Value from mysql');
$loop->addPeriodicTimer(1, function () use ($gauge) {
    $query = new Query();
    $query
        ->select('value')
        ->from('testTable')
        ->fetch()
        ->then(function (ArrayCollection $result) use ($gauge) {
            $query = new Query();
            $newValue = $result->first()['value'] + 1;
            $query
                ->update('testTable')
                ->set(['value' => $newValue])
                ->execute()
                ->then(function () use ($newValue, $gauge) {
                    $gauge->set($newValue);
                    echo $newValue . PHP_EOL;
                });
        });
});

// Example gauge from http
$client = new Browser(null, $loop);
$rates = $registry->getOrRegisterGauge(
    'rpe',
    'rates',
    'Value from http api ' . 'https://api.exchangerate.host/latest',
    ['ticker']
);
$loop->addPeriodicTimer(5, function () use ($client, $rates) {
    $client->get('https://api.exchangerate.host/latest')->then(function (ResponseInterface $response) use ($rates) {
        $json = json_decode((string) $response->getBody());
        foreach ($json->rates as $ticker => $rate) {
            $rates->set($rate, [$ticker]);
        }
    });
});
```

```text
# HELP rpe_rates Value from http api https://api.exchangerate.host/latest
# TYPE rpe_rates gauge
rpe_rates{ticker="AED"} 3.745518
rpe_rates{ticker="AFN"} 91.629743
rpe_rates{ticker="ALL"} 116.842695
rpe_rates{ticker="AMD"} 418.291639
rpe_rates{ticker="ANG"} 1.836239
rpe_rates{ticker="AOA"} 440.461947
rpe_rates{ticker="ARS"} 131.771094
rpe_rates{ticker="AUD"} 1.478021
```

### Other links

-   https://github.com/reactphp/http - HTTP client
-   http://socketo.me/docs/http - Ratchet HTTP\WS server
-   https://wiki.php.net/rfc/fibers - PHP RFC
